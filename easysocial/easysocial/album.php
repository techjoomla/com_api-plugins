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

class EasysocialApiResourceAlbum extends ApiResource
{
	public function get()
	{
	$this->plugin->setResponse($this->get_album());			
	}	
	public function post()
	{
	$this->plugin->setResponse($this->create_album());	
	}
	public function delete()
	{
	$this->plugin->setResponse($this->delete_album());
	}
	//this function is used to get the photos from particular album
	public function get_album()
	{
		$app = JFactory::getApplication();
		$album_id = $app->input->get('album_id',0,'INT');
		$uid = $app->input->get('uid',0,'INT');						
		$mapp = new EasySocialApiMappingHelper();
		$log_user= $this->plugin->get('user')->id;
		$mydata['album_id']=$album_id;
		$mydata['uid']=$uid;
		
		
		$ob = new EasySocialModelPhotos();
		$photos = $ob->getPhotos($mydata);				
		//loading photo table	
		$photo  = FD::table( 'Photo' );
		foreach($photos as $pnode )
		{		 
         $photo->load($pnode->id);
         $pnode->image_large = $photo->getSource('large');
         $pnode->image_square = $photo->getSource('square');
         $pnode->image_thumbnail = $photo->getSource('thumbnail');
         $pnode->image_featured = $photo->getSource('featured');		
		}
		//mapping function
		$all_photos = $mapp->mapItem($photos,'photos',$log_user);		
		return $all_photos;					
	}
	//this function is used to delete photos from album	
	public function delete_album()
	{
		$app = JFactory::getApplication();
		$id = $app->input->get('id',0,'INT');
		$album	= FD::table( 'Album' );
		$album->load( $id );
		if(!$album->id || !$id)
		{
			$result->status=0;
			$result->message='album not exists';
			return $result;			
		}
		else
		{
			$val =$album->delete();	
			$album->assignPoints( 'photos.albums.delete' , $album->uid );
			$val->message = 'album deleted successfully';		
			return $val;
		}
	}	
	//this function is used to create album	
	public function create_album()
	{
		$mapp = new EasySocialApiMappingHelper();
		// Get the uid and type
		$uid 	= JRequest::getInt( 'uid' );
		$type 	= JRequest::getWord( 'type' , SOCIAL_TYPE_USER );

		// Get the current logged in user.
		$my = FD::user();

		// Get the data from request.
		$post = JRequest::get( 'post' );

		// Load the album
		$album	= FD::table( 'Album' );
		$album->load( $post[ 'id' ] );		

		// Determine if this item is a new item
		$isNew 	= true;
			
		if( $album->id )
		{
			$isNew = false;
		}		
		// Load the album's library
		$lib = FD::albums( $uid , $type );

		// Check if the person is allowed to create albums
		if( $isNew && !$lib->canCreateAlbums() )
		{
			$view->setMessage( JText::_( 'COM_EASYSOCIAL_ALBUMS_ACCESS_NOT_ALLOWED' ) , SOCIAL_MSG_ERROR );
			return $view->call( __FUNCTION__ );
		}
		// Set the album uid and type
		$album->uid 			= $uid;
		$album->type 			= $type;		
		// Determine if the user has already exceeded the album creation
		if( $isNew && $lib->exceededLimits() )
		{
			$view->setMessage( JText::_( 'COM_EASYSOCIAL_ALBUMS_ACCESS_EXCEEDED_LIMIT' ) , SOCIAL_MSG_ERROR );
			return $view->call( __FUNCTION__ );
		}
		// Set the album creation alias
		$album->assigned_date 	= FD::date()->toMySQL();
		// Set custom date
		if( isset( $post['date'] ) )
		{
			$album->assigned_date = $post[ 'date' ];

			unset( $post['date'] );
		}
		
		//~ $photo_obj = $this->uploadPhoto($uid,$type);
		//~ 
		//~ $album->params=$photo_obj;	
			
		// Map the remaining post data with the album.
		$album->bind( $post );
		// Set the user creator
		$album->user_id	= $my->id;
		// Try to store the album
		$state = $album->store();
		// Throw error when there's an error saving album
		if( !$state )
		{			
			$view->setMessage( $album->getError() , SOCIAL_MSG_ERROR );
			return $view->call( __FUNCTION__ );
		}
		// Detect for location
		$address 	= JRequest::getVar( 'address' , '' );
		$latitude 	= JRequest::getVar( 'latitude' , '' );
		$longitude	= JRequest::getVar( 'longitude' , '' );

		if( !empty( $address ) && !empty( $latitude) && !empty( $longitude ) )
		{
			$location = FD::table('Location');
			$location->load(array('uid' => $album->id, 'type' => SOCIAL_TYPE_ALBUM));

			$location->uid = $album->id;
			$location->type = SOCIAL_TYPE_ALBUM;
			$location->user_id = $my->id;
			$location->address = $address;
			$location->longitude = $longitude;
			$location->latitude = $latitude;
			$location->store();
		}
		// Set the privacy for the album
		$privacy 		= JRequest::getWord( 'privacy' );
		$customPrivacy  = JRequest::getString( 'privacyCustom', '' );
		// Set the privacy through our library
		$lib->setPrivacy( $privacy , $customPrivacy );		
		$photo_obj = $this->uploadPhoto($uid,$type);				
		$album->params=$photo_obj;
						
	return $album;
	}
	 public function uploadPhoto($log_usr=0,$type=null)
	{
		//FD::checkToken();
        // Only registered users should be allowed to upload photos
        //FD::requireLogin();
        // Get the current view
        //$view   = $this->getCurrentView();
        // Get current user.
        $my     = FD::user();
        // Load up the configuration
        $config     = FD::config();
        // Check if the photos is enabled
        if (!$config->get('photos.enabled')) {
            $view->setMessage(JText::_('COM_EASYSOCIAL_ALBUMS_PHOTOS_DISABLED'), SOCIAL_MSG_ERROR);
            return "not enabled";
        }
        // Load the album table     
		 $album = FD::table( 'Album' );
         $album->load( $album->id );        

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

        // Check if the upload is for profile pictures
        if (!$isAvatar) {
            // Check if the person exceeded the upload limit
            if ($lib->exceededUploadLimit()) {
                $view->setMessage( $lib->getErrorMessage( 'upload.exceeded' ) , SOCIAL_MSG_ERROR );
                return "limit exceeds";
            }

            // Check if the person exceeded the upload limit
            if ($lib->exceededDiskStorage()) {
                $view->setMessage($lib->getErrorMessage(), SOCIAL_MSG_ERROR);
                return " your album limit is cross";
            }

            // Check if the person exceeded their daily upload limit
            if ($lib->exceededDailyUploadLimit()) {
                $view->setMessage( $lib->getErrorMessage( 'upload.daily.exceeded' ) , SOCIAL_MSG_ERROR );
                return "your daily limit is over";
            }
        }
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

        if (!$state) {
            $view->setMessage( JText::_( 'COM_EASYSOCIAL_PHOTOS_UPLOAD_ERROR_STORING_DB' ) , SOCIAL_MSG_ERROR );
            return "try to save";
        }

        // If album doesn't have a cover, set the current photo as the cover.
        if (!$album->hasCover()) {
            $album->cover_id    = $photo->id;

            // Store the album
            $album->store();
        }

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
