<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/photos.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/tables/album.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';
require_once JPATH_SITE.'/components/com_easysocial/controllers/albums.php';


class EasysocialApiResourcePhotoadd extends ApiResource
{
	public function post()
	{
	$this->plugin->setResponse($this->add_photo());	
	}
	public function add_photo()
	{
		$app = JFactory::getApplication();
		$userid = $app->input->get('userid',0,'INT');
		$album_id = $app->input->get('album_id',0,'INT');
		// Load the album
		$album	= FD::table( 'Album' );
		$album->load($album_id);		
		$photo_obj = $this->uploadPhoto($userid,$album_id);				
		$album->params=$photo_obj;
		return $album;	 	
	}	
	public function uploadPhoto($log_usr=0,$album_id)
	{
	    $my     = FD::user();
        // Load up the configuration
        $config     = FD::config();
        // Load the album table     
		 $album = FD::table( 'Album' );
         $album->load( $album_id );  
        // Check if the album id provided is valid
        if (!$album->id || !$album->id) {			
            //$view->setMessage(JText::_('COM_EASYSOCIAL_PHOTOS_INVALID_ALBUM_ID_PROVIDED'), SOCIAL_MSG_ERROR);
            return "album not valid";
        }

        // Get the uid and the type
        $uid        = $album->uid;
        $type       = $album->type;

        // Load the photo library
        $lib = FD::photo( $uid , $type );
    
        // Set uploader options
        $options = array( 'name' => 'file', 'maxsize' => $lib->getUploadFileSizeLimit() );

        // Get uploaded file
        $file   = FD::uploader( $options )->getFile();
        // If there was an error getting uploaded file, stop.
        if ($file instanceof SocialException) {
            $view->setMessage( $file );
            return "file not valid";
        }
        // Load the image object
        $image = FD::image();
        $image->load($file['tmp_name'], $file['name']);
        // Detect if this is a really valid image file.
        if (!$image->isValid()) {
            $view->setMessage( JText::_( 'COM_EASYSOCIAL_PHOTOS_INVALID_FILE_PROVIDED' ) , SOCIAL_MSG_ERROR );
            return "not image";
        }
        // Bind the photo data now
        $photo              = FD::table( 'Photo' );
        $photo->uid         = $uid;
        $photo->type        = $type;
        $photo->user_id     = $my->id;
        $photo->album_id    = $album->id;
        $photo->title       = $file[ 'name' ];
        $photo->caption     = '';
        $photo->ordering    = 0;
        $photo->state       = SOCIAL_STATE_PUBLISHED;
        // Set the creation date alias
        $photo->assigned_date   = FD::date()->toMySQL();
        // Cleanup photo title.
        $photo->cleanupTitle();

        // Trigger rules that should occur before a photo is stored
        $photo->beforeStore($file , $image);
        // Try to store the photo.
        $state      = $photo->store();
        //~ print_r($state);
        //~ die("checking state");
        if (!$state) {
            $view->setMessage( JText::_( 'COM_EASYSOCIAL_PHOTOS_UPLOAD_ERROR_STORING_DB' ) , SOCIAL_MSG_ERROR );
            return "try to save";
        }

        // If album doesn't have a cover, set the current photo as the cover.
        //~ if (!$album->hasCover()) {
            //~ $album->cover_id    = $photo->id;
//~ 
            //~ // Store the album
            //~ $album->store();
        //~ }

        // Get the photos library
        $photoLib   = FD::get('Photos', $image);

        // Get the storage path for this photo
        $storageContainer = FD::cleanPath($config->get('photos.storage.container'));
        $storage    = $photoLib->getStoragePath($album->id, $photo->id);
        $paths      = $photoLib->create($storage);

        // We need to calculate the total size used in each photo (including all the variants)
        $totalSize  = 0;

        // Create metadata about the photos
        if($paths) {

            foreach ($paths as $type => $fileName) {
                $meta               = FD::table( 'PhotoMeta' );
                $meta->photo_id     = $photo->id;
                $meta->group        = SOCIAL_PHOTOS_META_PATH;
                $meta->property     = $type;
                // do not store the container path as this path might changed from time to time
                $tmpStorage = str_replace('/' . $storageContainer . '/', '/', $storage);
                $meta->value = $tmpStorage . '/' . $fileName;
                $meta->store();

                // We need to store the photos dimension here
                list($width, $height, $imageType, $attr) = getimagesize(JPATH_ROOT . $storage . '/' . $fileName);

                // Set the photo size
                $totalSize += filesize(JPATH_ROOT . $storage . '/' . $fileName);

                // Set the photo dimensions
                $meta               = FD::table('PhotoMeta');
                $meta->photo_id     = $photo->id;
                $meta->group        = SOCIAL_PHOTOS_META_WIDTH;
                $meta->property     = $type;
                $meta->value        = $width;
                $meta->store();

                $meta               = FD::table('PhotoMeta');
                $meta->photo_id     = $photo->id;
                $meta->group        = SOCIAL_PHOTOS_META_HEIGHT;
                $meta->property     = $type;
                $meta->value        = $height;
                $meta->store();
            }
        }

        // Set the total photo size
        $photo->total_size = $totalSize;
        $photo->store();

        // After storing the photo, trigger rules that should occur after a photo is stored
        $photo->afterStore($file, $image);

        // Determine if we should create a stream item for this upload
        $createStream   = JRequest::getBool( 'createStream' );

        // Add Stream when a new photo is uploaded
        if ($createStream) {
            $photo->addPhotosStream( 'create' );
        }

        if ($isAvatar) {
            return $photo;
        }
		// After storing the photo, trigger rules that should occur after a photo is stored		
		return $photo; 
	}	
}
