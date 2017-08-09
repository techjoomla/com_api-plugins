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

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/photos.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/tables/album.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';
require_once JPATH_SITE . '/components/com_easysocial/controllers/albums.php';

/**
 * API class PlgAPIEasysocial
 *
 * @since  1.0
 */
class EasysocialApiResourcePhotoadd extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->plugin->setResponse($this->add_photo());
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function add_photo()
	{
		$app			=	JFactory::getApplication();
		$userid			=	$app->input->get('userid', 0, 'INT');
		$album_id		=	$app->input->get('album_id', 0, 'INT');

		// Load the album
		$album			=	FD::table('Album');
		$album->load($album_id);
		$photo_obj		=	new EasySocialApiUploadHelper;
		$addphoto		=	$photo_obj->addPhotoAlbum($album_id, $userid);
		$album->params	=	$addphoto;
		$album->title	=	JText::_($album->title);
		$album->caption	=	JText::_($album->caption);

		return $album;
	}
}
