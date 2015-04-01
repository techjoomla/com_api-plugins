<?php

defined('_JEXEC') or die('Restricted access');
jimport( 'libraries.schema.group' );

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/group.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/message.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/discussion.php';

class EasySocialApiUploadHelper
{
	//upload cover photo
	public function uploadCover($uid = 0,$type=SOCIAL_TYPE_USER)
	{
		$result = new stdClass;

		if( !$uid && !$type )
		{

			$result->status  = 0;
			$result->message = 'Empty uid / type not allowed for upload';
			return $result; 
		}

		// Load the photo library now since we have the unique keys
		$lib 	= FD::photo( $uid , $type );

		// Check if the user is allowed to upload cover photos
		if( !$lib->canUploadCovers() )
		{
			$result->status  = 0;
			$result->message = 'user not allowed for upload cover';
			return $result;
		}

		// Get the current logged in user.
		$my 	= FD::user($uid);

		// Set uploader options
		$options = array( 'name' => 'cover_file' , 'maxsize' => $lib->getUploadFileSizeLimit() );

		// Get uploaded file
		$file = FD::uploader( $options )->getFile();

		// Load the image
		$image 	= FD::image();
		$image->load( $file[ 'tmp_name' ] , $file[ 'name' ] );

		// Check if there's a profile photos album that already exists.
		$model	= FD::model( 'Albums' );

		// Retrieve the user's default album
		$album 	= $model->getDefaultAlbum( $uid , $type , SOCIAL_ALBUM_PROFILE_COVERS );

		$photo 				= FD::table( 'Photo' );
		$photo->uid 		= $uid;
		$photo->type 		= $type;
		$photo->user_id 	= $my->id;
		$photo->album_id 	= $album->id;
		$photo->title 		= $file[ 'name' ];
		$photo->caption 	= '';
		$photo->ordering	= 0;
		$photo->assigned_date 	= FD::date()->toMySQL();

		// Trigger rules that should occur before a photo is stored
		$photo->beforeStore( $file , $image );

		// Try to store the photo.
		$state 		= $photo->store();

		if( !$state )
		{
			$result->status  = 0;
			$result->message = 'unable to create cover file';
			return $result;
		}

		// Trigger rules that should occur after a photo is stored
		$photo->afterStore( $file , $image );

		// If album doesn't have a cover, set the current photo as the cover.
		if( !$album->hasCover() )
		{
			$album->cover_id 	= $photo->id;

			// Store the album
			$album->store();
		}

		// Render photos library
		$photoLib 	= FD::get( 'Photos' , $image );
		$storage 	= $photoLib->getStoragePath($album->id, $photo->id);
		$paths 		= $photoLib->create($storage);

		// Create metadata about the photos
		foreach( $paths as $type => $fileName )
		{
			$meta 				= FD::table( 'PhotoMeta' );
			$meta->photo_id		= $photo->id;
			$meta->group 		= SOCIAL_PHOTOS_META_PATH;
			$meta->property 	= $type;
			$meta->value		= $storage . '/' . $fileName;

			$meta->store();
		}
		return $photo;
	}
	//upload image
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
}
