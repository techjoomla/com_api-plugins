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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/story/story.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceShare extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse('Use post method to share');
	}

	public function post()
	{
		$app = JFactory::getApplication();

		//$share_for = $app->input->get('share_for','','CMD');
		
		$type = $app->input->get('type','story','STRING');
		
		$content = $app->input->get('content','','RAW');

		//$targetId = $app->input->get('target_id','All','raw');
		
		$cluster = $app->input->get('group_id',null,'INT');
		
		$log_usr = $this->plugin->get('user')->id;
		//now take login user stream for target
		$targetId = $log_usr;
		
		$valid = 1;
		$result = new stdClass;
		
		$story = FD::story(SOCIAL_TYPE_USER);

		if(empty($type))
		{
			$result->id = 0;
			$result->status  = 0;
			$result->message = 'Empty type not allowed';
			$valid = 0;
		}
		else if($valid)
		{
		
			// Determines if the current posting is for a cluster
			$cluster = isset($cluster) ? $cluster : 0;
			$clusterType = ($cluster) ? 'group' : null;
			$isCluster = $cluster ? true : false;

			if ($isCluster) {
				
				$group = FD::group($cluster);
				$permissions = $group->getParams()->get('stream_permissions', null);

				if($permissions == null)
				{
					$result->id = 0;
					$result->status  = 0;
					$result->message = 'This group do not have share data permission';
					
					$this->plugin->setResponse($result);
					return true;
				}
			}

			$privacyRule = ( $type == 'photos' ) ? 'photos.view' : 'story.view';

			// Options that should be sent to the stream lib
			$args = array(
							'content' => $content,
							'targetId'		=> $targetId,
							'location'		=> null,
							'with'			=> null,
							'mentions'		=> null,
							'cluster'		=> $cluster,
							'clusterType'	=> $clusterType,
							'mood'			=> null,
							'privacyRule'	=> 'public',
							'privacyValue'	=> '',
							'privacyCustom'	=> $privacyRule
						);

			$photo_ids = array();
			$args['actorId'] = $log_usr;
			$args['contextIds'] = 0;
			$args['contextType']	= $type;
			
			if($type == 'photos')
			{
				$photo_ids[] = $this->uploadPhoto($log_usr,$type);
				$args['contextIds'] = (count($photo_ids))?$photo_ids:null;
			}

			// Create the stream item
			$stream = $story->create($args);

			// Add badge for the author when a report is created.
			$badge = FD::badges();
			$badge->log('com_easysocial', 'story.create', $log_usr, JText::_('Posted a new update'));

			// @points: story.create
			// Add points for the author when a report is created.
			$points = FD::points();
			$points->assign('story.create', 'com_easysocial', $log_usr);
			
			if($stream->id)
			{
				$result->id = $stream->id;
				$result->status  =1;
				$result->message = 'data share successfully';
			}

		}
		
	   $this->plugin->setResponse($result);
	}
	
	//function for upload photo
	public function uploadPhoto($log_usr=0,$type=null)
	{
		// Get current logged in user.
		$my = FD::user($log_usr);

		// Get user access
		$access = FD::access( $my->id , SOCIAL_TYPE_USER );

		// Load up the photo library
		$lib  = FD::photo($log_usr, $type);
		
		// Define uploader options
		$options = array( 'name' => 'file', 'maxsize' => $lib->getUploadFileSizeLimit() );

		// Get uploaded file
		$file   = FD::uploader($options)->getFile();

		// Load the iamge object
		$image  = FD::image();
		$image->load( $file[ 'tmp_name' ] , $file[ 'name' ] );

		// Detect if this is a really valid image file.
		if( !$image->isValid() )
		{
			return "invalid image";
		}
		
		// Load up the album's model.
		$albumsModel    = FD::model( 'Albums' );

		// Create the default album if necessary
		$album  = $albumsModel->getDefaultAlbum( $log_usr , $type , SOCIAL_ALBUM_STORY_ALBUM );

		// Bind photo data
		$photo              = FD::table( 'Photo' );
		$photo->uid         = $uid;
		$photo->type        = $type;
		$photo->user_id     = $my->id;
		$photo->album_id    = $album->id;
		$photo->title       = $file[ 'name' ];
		$photo->caption     = '';
		$photo->ordering    = 0;

		// Set the creation date alias
		$photo->assigned_date   = FD::date()->toMySQL();

		// Trigger rules that should occur before a photo is stored
		$photo->beforeStore( $file , $image );

		// Try to store the photo.
		$state      = $photo->store();
		
		 // Load the photos model
		$photosModel    = FD::model( 'Photos' );

		// Get the storage path for this photo
		$storage    = FD::call( 'Photos' , 'getStoragePath' , array( $album->id , $photo->id ) );

		// Get the photos library
		$photoLib   = FD::get( 'Photos' , $image );
		$paths      = $photoLib->create($storage);
		
		// Create metadata about the photos
		if( $paths )
		{
			foreach( $paths as $type => $fileName )
			{
				$meta               = FD::table( 'PhotoMeta' );
				$meta->photo_id     = $photo->id;
				$meta->group        = SOCIAL_PHOTOS_META_PATH;
				$meta->property     = $type;
				$meta->value        = $storage . '/' . $fileName;

				$meta->store();

				// We need to store the photos dimension here
				list($width, $height, $imageType, $attr) = getimagesize(JPATH_ROOT . $storage . '/' . $fileName);

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

		// After storing the photo, trigger rules that should occur after a photo is stored
		//$photo->afterStore( $file , $image );

		return $photo->id; 
	}
	
	//function for upload file
	public function uploadFile()
	{
		$config = FD::config();
		$limit 	= $config->get( $type . '.attachments.maxsize' );

		// Set uploader options
		$options = array(
			'name'        => 'file',
			'maxsize' => $limit . 'M'
		);
		// Let's get the temporary uploader table.
		$uploader 			= FD::table( 'Uploader' );
		$uploader->user_id	= $this->plugin->get('user')->id;

		// Pass uploaded data to the uploader.
		$uploader->bindFile( $data );

		$state 	= $uploader->store();
	}
	
}
