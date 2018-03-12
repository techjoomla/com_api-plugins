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
		$this->getAlbumImages();
	}

	/** POST Call
	 * 
	 * @return	array	message
	 */

	public function post()
	{
		$this->createAlbum();
	}

	/** DELETE Call
	 * 
	 * @return  mixed
	 */
	public function delete()
	{
		return $this->deleteCheck();
	}

	/** switch case for photo delete or album delete. 
	 * 
	 * @return  ApiPlugin response object
	 */
	private function deleteCheck()
	{
		$app	=	JFactory::getApplication();
		$flag	=	$app->input->get('flag', '', 'CMD');
		$res	=	new stdClass;

		switch ($flag)
		{
			case 'deletephoto':	$res->result->message = $this->deletePhoto();
								break;
			case 'deletealbum':	$res->result->message = $this->deleteAlbum();
								break;
		}

		$this->plugin->setResponse($res);
	}

	/** 
	 * This function is use to delete photo from album
	 * 
	 * @return	array messages
	 */

	private function deletePhoto()
	{
		$user	=	JFactory::getUser($this->plugin->get('user')->id);
		$app	=	JFactory::getApplication();
		$id		=	$app->input->get('id', 0, 'INT');

		// Load the photo table
		$photo	=	ES::table('Photo');
		$photo->load($id);
		$lib	=	ES::photo($photo->uid, $photo->type, $photo);

		if (!$id && !$photo->id)
		{
			ApiError::raiseError(400, JText::_('COM_EASYSOCIAL_PHOTOS_INVALID_ID_PROVIDED'));
		}

		// Load the photo library & Test if the user is allowed to delete the photo

		if (!$lib->deleteable())
		{
			ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_PHOTOS_NO_PERMISSION_TO_DELETE_PHOTO'));
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
	private function getAlbumImages()
	{
		$mapp				=	new EasySocialApiMappingHelper;
		$app				=	JFactory::getApplication();
		$album_id			=	$app->input->get('album_id', 0, 'INT');
		$uid				=	$app->input->get('uid', 0, 'INT');
		$state				=	$app->input->get('state', 0, 'INT');
		$log_user			=	$this->plugin->get('user')->id;
		$limitstart			=	$app->input->get('limitstart', 0, 'INT');
		$limit				=	$app->input->get('limit', 10, 'INT');
		$mydata['album_id']	=	$album_id;
		$mydata['uid']		=	$uid;
		$mydata['start']	=	$limitstart;
		$mydata['limit']	=	$limit;

		$ob					=	new EasySocialModelPhotos;
		$photos				=	$ob->getPhotos($mydata);

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		// Loading photo table
		$photo				=	ES::table('Photo');

		foreach ($photos as $pnode )
		{
			$photo->load($pnode->id);
			$pht_lib				=	ES::photo($pnode->id, 'event', $album_id);
			$photo->cluser_user		=	$pht_lib->creator()->id;
			$pnode->image_large		=	$photo->getSource('large');
			$pnode->image_square	=	$photo->getSource('square');
			$pnode->image_thumbnail	=	$photo->getSource('thumbnail');
			$pnode->image_featured	=	$photo->getSource('featured');
		}

		// Mapping function
		$all_photos					=	$mapp->mapItem($photos, 'photos', $log_user);

		if (count($all_photos) < 1)
		{
			$res->empty_message	=	JText::_('COM_EASYSOCIAL_NO_ITEMS_FOUND');
		}
		else
		{
			$res->result	=	$all_photos;
		}

		$this->plugin->setResponse($res);
	}

	/**
	 *  this function is used to delete photos from album 
	 * 
	 * @return	string		message
	 */

	private function deleteAlbum()
	{
		$app	=	JFactory::getApplication();
		$id		=	$app->input->get('id', 0, 'INT');
		$album	=	ES::table('Album');
		$album->load($id);

		if (!$album->id || !$id)
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_ALBUM_NOT_EXISTS'));
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

	private function createAlbum()
	{
		// Get the uid and type
		$app	=	JFactory::getApplication();
		$uid	=	$app->input->get('uid', 0, 'INT');
		$type	=	$app->input->get('type', 0, 'STRING');
		$title	=	$app->input->get('title', 0, 'STRING');

		// Load the album
		$album	=	ES::table('Album');
		$album->load();

		$canCreate = ES::user();

		// Check if the user really has access to create event
		if (! $canCreate->getAccess()->allowed('albums.create') && ! $canCreate->isSiteAdmin())
		{
			ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_ALBUMS_ACCESS_NOT_ALLOWED'));
		}

		// Determine if this item is a new item
		$isNew	=	true;

		if ($album->id)
		{
			$isNew	=	false;
		}

		// Load the album's library
		$lib = ES::albums($uid, $type);

		// Check if the person is allowed to create albums

		if ($isNew && !$lib->canCreateAlbums())
		{
			ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_ALBUMS_ACCESS_NOT_ALLOWED'));
		}

		// Set the album uid and type
		$album->uid  = $uid;
		$album->type = $type;
		$album->title = $title;

		// Determine if the user has already exceeded the album creation
		if ($isNew && $lib->exceededLimits())
		{
			ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_ALBUMS_EXCEEDED'));
		}

		// Set the album creation alias
		$album->assigned_date 	= ES::date()->toMySQL();

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
			ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_ALBUMS_EXCEEDED'));
		}

		$photo_obj		=	new EasySocialApiUploadHelper;
		$photodata		=	$photo_obj->albumPhotoUpload($album->id, $uid, $type);

		$album->params	=	$photodata;

		if (empty($album->cover_id))
		{
			$album->cover_id	=	$photodata->id;

			// Store the album
			$album->store();
		}

		$this->plugin->setResponse($album);
	}
}
