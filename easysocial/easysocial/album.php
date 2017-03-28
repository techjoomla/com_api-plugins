<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/photos.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/tables/album.php';

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';
require_once JPATH_SITE . '/components/com_easysocial/controllers/albums.php';

/** Album API 
 * 
 * @since  1.8.8
 */
class EasysocialApiResourceAlbum extends ApiResource
{
	/** Get Call
	 * 
	 * @return	array	list of images
	 */

	public function get()
	{
		return $this->plugin->setResponse($this->get_album_images());
	}

	/** POST Call
	 * 
	 * @return	array	message
	 */

	public function post()
	{
		return $this->plugin->setResponse($this->create_album());
	}

	/** DELETE Call
	 * 
	 * @return	array	message
	 */
	public function delete()
	{
		return $this->plugin->setResponse($this->delete_check());
	}

	/** switch case for photo delete or album delete. 
	 * 
	 * @return	object
	 */
	public function delete_check()
	{
		$app	=	JFactory::getApplication();
		$flag	=	$app->input->get('flag', null, 'STRING');

		switch ($flag)
		{
			case 'deletephoto':	$result1 = $this->delete_photo();

							return $result1;
							break;
			case 'deletealbum':	$result = $this->delete_album();

							return $result;
							break;
		}
	}

	/** 
	 * This function is use to delete photo from album
	 * 
	 * @return	array		messages
	 */

	public function delete_photo()
	{
		$user	=	JFactory::getUser($this->plugin->get('user')->id);
		$app	=	JFactory::getApplication();
		$id		=	$app->input->get('id', 0, 'INT');
		$res	=	new stdClass;

		// Load the photo table
		$photo	=	FD::table('Photo');
		$photo->load($id);
		$lib	=	FD::photo($photo->uid, $photo->type, $photo);

		if (!$id && !$photo->id)
		{
			$res->state		=	false;
			$res->message	=	JText::_('COM_EASYSOCIAL_PHOTOS_INVALID_ID_PROVIDED');

			return $res;
		}

		// Load the photo library & Test if the user is allowed to delete the photo

		if (!$lib->deleteable())
		{
			$res->state		=	false;
			$res->message	=	JText::_('COM_EASYSOCIAL_PHOTOS_NO_PERMISSION_TO_DELETE_PHOTO');

			return $res;
		}

		// Try to delete the photo
		$state	=	$photo->delete();

		if (!$state)
		{
			return false;
		}
		else
		{
			return $state;
		}
	}

	/** 
	 * This function is used to get the photos from particular album 
	 * 
	 * @return	array		Album Images*
	 */
	public function get_album_images()
	{
		$mapp				=	new EasySocialApiMappingHelper;
		$app				=	JFactory::getApplication();
		$album_id			=	$app->input->get('album_id', 0, 'INT');
		$uid				=	$app->input->get('uid', 0, 'INT');
		$state				=	$app->input->get('state', 0, 'INT');
		$mapp				=	new EasySocialApiMappingHelper;
		$log_user			=	$this->plugin->get('user')->id;
		$limitstart			=	$app->input->get('limitstart', 0, 'INT');
		$limit				=	$app->input->get('limit', 10, 'INT');
		$mydata['album_id']	=	$album_id;
		$mydata['uid']		=	$uid;
		$mydata['start']	=	$limitstart;
		$mydata['limit']	=	$limit;

		$ob					=	new EasySocialModelPhotos;
		$photos				=	$ob->getPhotos($mydata);

		// Loading photo table
		$photo				=	FD::table('Photo');

		foreach ($photos as $pnode )
		{
			$photo->load($pnode->id);
			$pht_lib				=	FD::photo($pnode->id, 'event', $album_id);
			$photo->cluser_user		=	$pht_lib->creator()->id;
			$pnode->image_large		=	$photo->getSource('large');
			$pnode->image_square	=	$photo->getSource('square');
			$pnode->image_thumbnail	=	$photo->getSource('thumbnail');
			$pnode->image_featured	=	$photo->getSource('featured');
		}

		// Mapping function
		$all_photos					=	$mapp->mapItem($photos, 'photos', $log_user);

		return $all_photos;
	}

	/**
	 *  this function is used to delete photos from album 
	 * 
	 * @return	string		message
	 */

	public function delete_album()
	{
		$app	=	JFactory::getApplication();
		$id		=	$app->input->get('id', 0, 'INT');
		$album	=	FD::table('Album');
		$album->load($id);

		if (!$album->id || !$id)
		{
			$result->status		=	0;
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_ALBUM_NOT_EXISTS');

			return $result;
		}
		else
		{
			$val	=	$album->delete($id);
			$album->assignPoints('photos.albums.delete', $album->uid);
			$val	=	JText::_('PLG_API_EASYSOCIAL_ALBUM_DELETE_SUCCESS_MESSAGE');

			return $val;
		}
	}

	/** 
	 * this function is used to create album 
	 * 
	 * @return	string		message
	 */

	public function create_album()
	{
		// Get the uid and type
		$app	=	JFactory::getApplication();
		$uid	=	$app->input->get('uid', 0, 'INT');
		$type	=	$app->input->get('type', 0, 'USER');
		$title	=	$app->input->get('title', 0, 'USER');

		// Load the album
		$album	=	FD::table('Album');
		$album->load();
		$res	=	new stdClass;

		// Determine if this item is a new item
		$isNew	=	true;

		if ($album->id)
		{
			$isNew	=	false;
		}

		// Load the album's library
		$lib = FD::albums($uid, $type);

		// Check if the person is allowed to create albums

		if ($isNew && !$lib->canCreateAlbums())
		{
			$res->success	=	0;
			$res->message	=	JText::_('COM_EASYSOCIAL_ALBUMS_ACCESS_NOT_ALLOWED');

			return $res;
		}

		// Set the album uid and type
		$album->uid  = $uid;
		$album->type = $type;
		$album->title = $title;

		// Determine if the user has already exceeded the album creation
		if ($isNew && $lib->exceededLimits())
		{
			$res->success	=	0;
			$res->message	=	JText::_('COM_EASYSOCIAL_ALBUMS_EXCEEDED');

			return $res;
		}

		// Set the album creation alias
		$album->assigned_date 	= FD::date()->toMySQL();

		// Set custom date
		if (isset($post['date']))
		{
			$album->assigned_date = $post[ 'date' ];
			unset( $post['date'] );
		}

		// Set the user creator
		$album->user_id	=	$uid;

		// Try to store the album
		$state = $album->store();

		// Throw error when there's an error saving album

		if (!$state)
		{
			$res->success	=	0;
			$res->message	=	JText::_('COM_EASYSOCIAL_ALBUMS_EXCEEDED');

			return $res;
		}

		$photo_obj		=	new EasySocialApiUploadHelper;
		$photodata		=	$photo_obj->albumPhotoUpload($uid, $type, $album->id);
		$album->params	=	$photodata;

		if (!$album->cover_id)
		{
			$album->cover_id	=	$photodata->id;

			// Store the album
			$album->store();
		}

		return $album;
	}
}
