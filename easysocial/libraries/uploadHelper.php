<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */
defined('_JEXEC') or die('Restricted access');

jimport('libraries.schema.group');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/schema/group.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/schema/message.php';
require_once JPATH_SITE . '/media/com_easysocial/apps/fields/user/avatar/helper.php';
require_once JPATH_SITE . '/media/com_easysocial/apps/fields/user/cover/helper.php';

// Require_once JPATH_SITE.'/media/com_easysocial/apps/fields/user/cover/ajax.php';

/**
 * API class EasySocialApiUploadHelper
 *
 * @since  1.0
 */
class EasySocialApiUploadHelper
{
	/**
	 * Method uploadCover photo
	 *
	 * @param   integer  $uid   array of data
	 * @param   string   $type  object type
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function uploadCover($uid = 0,$type = SOCIAL_TYPE_USER)
	{
		$result = new stdClass;

		if (!$uid && !$type)
		{
			$result->status  = 0;
			$result->message = 'Empty uid / type not allowed for upload';

			return $result;
		}

		// Load the photo library now since we have the unique keys
		$lib = FD::photo($uid, $type);

		// Check if the user is allowed to upload cover photos
		if (!$lib->canUploadCovers())
		{
			$result->status  = 0;
			$result->message = 'user not allowed for upload cover';

			return $result;
		}

		// Get the current logged in user.
		$my 	= FD::user($uid);

		// Set uploader options
		$options = array('name' => 'cover_file', 'maxsize' => $lib->getUploadFileSizeLimit());

		// Get uploaded file
		$file = FD::uploader($options)->getFile();

		// Load the image
		$image 	= FD::image();
		$image->load($file[ 'tmp_name' ], $file[ 'name' ]);

		// Check if there's a profile photos album that already exists.
		$model	= FD::model('Albums');

		// Retrieve the user's default album
		$album 	= $model->getDefaultAlbum($uid, $type, SOCIAL_ALBUM_PROFILE_COVERS);

		$photo 				= FD::table('Photo');
		$photo->uid 		= $uid;
		$photo->type 		= $type;
		$photo->user_id 	= $my->id;
		$photo->album_id 	= $album->id;
		$photo->title 		= $file[ 'name' ];
		$photo->caption 	= '';
		$photo->ordering	= 0;
		$photo->assigned_date 	= FD::date()->toMySQL();

		// Trigger rules that should occur before a photo is stored
		$photo->beforeStore($file, $image);

		// Try to store the photo.
		$state 		= $photo->store();

		if (!$state)
		{
			$result->status  = 0;
			$result->message = 'unable to create cover file';

			return $result;
		}

		// If album doesn't have a cover, set the current photo as the cover.
		if (!$album->hasCover())
		{
			$album->cover_id 	= $photo->id;

			// Store the album
			$album->store();
		}

		// Render photos library
		$photoLib 	= FD::get('Photos', $image);
		$storage 	= $photoLib->getStoragePath($album->id, $photo->id);
		$paths 		= $photoLib->create($storage);

		// Create metadata about the photos
		foreach ($paths as $type => $fileName)
		{
			$meta 				= FD::table('PhotoMeta');
			$meta->photo_id		= $photo->id;
			$meta->group 		= SOCIAL_PHOTOS_META_PATH;
			$meta->property 	= $type;
			$meta->value		= $storage . '/' . $fileName;
			$meta->store();
		}

		// Load the cover
		$cover = FD::table('Cover');
		$state = $cover->load(array('uid' => 0, 'type' => $type));

		// Set the cover to pull from photo
		$cover->setPhotoAsCover($photo->id, 0.5, 0.5);

		// Save the cover.
		$cover->store();

		// Return meta data for cover photo object

		return $photo;
	}

	/**
	 * Method photo-album image upload function
	 *
	 * @param   integer  $log_usr  user data
	 * @param   string   $type     type
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function uploadPhoto($log_usr = 0,$type = null)
	{
		// Get current logged in user.
		$my = FD::user($log_usr);

		// Get user access
		$access = FD::access($my->id, SOCIAL_TYPE_USER);

		// Load up the photo library
		$lib  = FD::photo($log_usr, $type);

		// Define uploader options
		$options = array('name' => 'file', 'maxsize' => $lib->getUploadFileSizeLimit());

		// Get uploaded file
		$file   = FD::uploader($options)->getFile();

		// Load the iamge object
		$image  = FD::image();
		$image->load($file[ 'tmp_name' ], $file[ 'name' ]);

		// Detect if this is a really valid image file.

		if (!$image->isValid())
		{
			return "invalid image";
		}

		// Load up the album's model.
		$albumsModel    = FD::model('Albums');

		// Create the default album if necessary
		$album  = $albumsModel->getDefaultAlbum($log_usr, $type, SOCIAL_ALBUM_STORY_ALBUM);

		// Bind photo data
		$photo              = FD::table('Photo');
		$photo->uid         = '';
		$photo->type        = $type;
		$photo->user_id     = $my->id;
		$photo->album_id    = $album->id;
		$photo->title       = $file[ 'name' ];
		$photo->caption     = '';
		$photo->ordering    = 0;

		// Set the creation date alias
		$photo->assigned_date   = FD::date()->toMySQL();

		// Trigger rules that should occur before a photo is stored
		$photo->beforeStore($file, $image);

		// Try to store the photo.
		$state      = $photo->store();

		// Load the photos model
		$photosModel    = FD::model('Photos');

		// Get the storage path for this photo
		$storage    = FD::call('Photos', 'getStoragePath', array($album->id ,$photo->id));

		// Get the photos library
		$photoLib   = FD::get('Photos', $image);
		$paths      = $photoLib->create($storage);

		// Create metadata about the photos
		if ($paths)
		{
			foreach ($paths as $type => $fileName )
			{
				$meta               = FD::table('PhotoMeta');
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
		// $photo->afterStore( $file , $image );

		return $photo;
	}

	/**
	 * Method To create temp group avtar data
	 *
	 * @param   object  $file  file object
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function ajax_avatar($file)
	{
		// Get the ajax library
		$ajax 		= FD::ajax();

		// Load up the image library so we can get the appropriate extension
		$image 	= FD::image();
		$image->load($file['tmp_name']);

		// Copy this to temporary location first
		$tmpPath	= SocialFieldsUserAvatarHelper::getStoragePath('file');
		$tmpName	= md5($file[ 'name' ] . 'file' . FD::date()->toMySQL()) . $image->getExtension();

		$source 	= $file['tmp_name'];
		$target 	= $tmpPath . '/' . $tmpName;
		$state 		= JFile::copy($source, $target);

		$tmpUri		= SocialFieldsUserAvatarHelper::getStorageURI('file');
		$uri 		= $tmpUri . '/' . $tmpName;

		$ajax->resolve($file, $uri, $target);

		$data = array();
		$data['temp_path'] = $target;
		$data['temp_uri'] = $uri;

		return $data;
	}

	// To create temp group cover data
	/**
	 * Method to create temp group cover data
	 *
	 * @param   object  $file   file object
	 * @param   string  $uname  file name
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function ajax_cover($file, $uname = 'cover_file')
	{
		/*
		$cls_obj = new SocialFieldsUserCover();
		$cover_obj = $cls_obj->createCover($file,$uname);

		return $cover_obj;
		*/

		// Load our own image library
		$image = FD::image();

		// Generates a unique name for this image.
		$name = $file['name'];

		// Load up the file.
		$image->load($file['tmp_name'], $name);

		// Ensure that the image is valid.
		if (!$image->isValid())
		{
			return false;

			// Need error code here
		}

		// Get the storage path
		$storage = SocialFieldsUserCoverHelper::getStoragePath($uname);

		// Create a new avatar object.
		$photos = FD::get('Photos', $image);

		// Create avatars
		$sizes = $photos->create($storage);

		// We want to format the output to get the full absolute url.
		$base = basename($storage);

		$result = array();

		foreach ($sizes as $size => $value)
		{
			$row = new stdClass;

			$row->title	= $file['name'];
			$row->file = $value;
			$row->path = JPATH_ROOT . '/media/com_easysocial/tmp/' . $base . '/' . $value;
			$row->uri = rtrim(JURI::root(), '/') . '/media/com_easysocial/tmp/' . $base . '/' . $value;

			$result[$size] = $row;
		}

		return $result;
	}

	// Photo-album image upload function
	/**
	 * Method photo-album image upload function
	 *
	 * @param   int      $id       user id
	 * @param   integer  $log_usr  user data
	 * @param   string   $type     type
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function albumPhotoUpload($id, $log_usr = 0, $type = null)
	{
		// Load up the configuration
		$config     = FD::config();

		// Check if the photos is enabled
		if (!$config->get('photos.enabled'))
		{
			return false;
		}

		// Load the album table
		$album = FD::table('Album');
		$album->load($id);

		// Check if the album id provided is valid
		if (!$album->id || !$album->id)
		{
			return false;
		}

		// Get the uid and the type
		$uid        = $album->uid;
		$type       = $album->type;

		// Load the photo library
		$lib = FD::photo($uid, $type);

		// Set uploader options
		$options = array( 'name' => 'file', 'maxsize' => $lib->getUploadFileSizeLimit() );

		// Get uploaded file
		$file   = FD::uploader($options)->getFile();

		// If there was an error getting uploaded file, stop.
		if ($file instanceof SocialException)
		{
			return false;
		}

		// Load the image object
		$image = FD::image();
		$image->load($file['tmp_name'], $file['name']);

		// Detect if this is a really valid image file.
		if (!$image->isValid())
		{
			return false;
		}

		// Bind the photo data now

		$photo              = FD::table('Photo');
		$photo->uid         = $uid;
		$photo->type        = $type;
		$photo->user_id     = $album->uid;
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
		$photo->beforeStore($file, $image);

		// Try to store the photo.
		$state      = $photo->store();

		if (!$state)
		{
			return false;
		}
		// If album doesn't have a cover, set the current photo as the cover.

		if (!$album->hasCover())
		{
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
		if ($paths)
		{
			foreach ($paths as $type => $fileName)
			{
				$meta               = FD::table('PhotoMeta');
				$meta->photo_id     = $photo->id;
				$meta->group        = SOCIAL_PHOTOS_META_PATH;
				$meta->property     = $type;

				// Do not store the container path as this path might changed from time to time
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
		$createStream   = JRequest::getBool('createStream');

		// Add Stream when a new photo is uploaded
		if ($createStream)
		{
			$photo->addPhotosStream('create');
		}

		// After storing the photo, trigger rules that should occur after a photo is stored

		return $photo;
	}

	// Add photos in album
	/**
	 * Method map
	 *
	 * @param   integer  $album_id  object type
	 * @param   integer  $log_usr   array of data
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function addPhotoAlbum($album_id, $log_usr = 0)
	{
		$my     = FD::user();

		// Load up the configuration
		$config     = FD::config();

		// Load the album table
		$album = FD::table('Album');
		$album->load($album_id);

		// Check if the album id provided is valid
		if (!$album->id || !$album->id)
		{
			return "album not valid";
		}

		// Get the uid and the type
		$uid        = $album->uid;
		$type       = $album->type;

		// Load the photo library
		$lib = FD::photo($uid, $type);

		// Set uploader options
		$options = array('name' => 'file', 'maxsize' => $lib->getUploadFileSizeLimit());

		// Get uploaded file
		$file   = FD::uploader($options)->getFile();

		// If there was an error getting uploaded file, stop.

		if ($file instanceof SocialException)
		{
			return false;
		}

		// Load the image object
		$image = FD::image();
		$image->load($file['tmp_name'], $file['name']);

		// Detect if this is a really valid image file.

		if (!$image->isValid())
		{
			return false;
		}

		// Bind the photo data now
		$photo              = FD::table('Photo');
		$photo->uid         = $uid;
		$photo->type        = $type;
		$photo->user_id     = $album->uid;
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
		$photo->beforeStore($file, $image);

		// Try to store the photo.
		$state      = $photo->store();

		if (!$state)
		{
			return false;
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
		if ($paths)
		{
			foreach ($paths as $type => $fileName)
			{
				$meta               = FD::table('PhotoMeta');
				$meta->photo_id     = $photo->id;
				$meta->group        = SOCIAL_PHOTOS_META_PATH;
				$meta->property     = $type;

				// Do not store the container path as this path might changed from time to time
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
		$createStream = JRequest::getBool('createStream');

		// Add Stream when a new photo is uploaded
		if ($createStream)
		{
			$photo->addPhotosStream('create');
		}

		if ($isAvatar)
		{
			return $photo;
		}
		// After storing the photo, trigger rules that should occur after a photo is stored

		return $photo;
	}
}
