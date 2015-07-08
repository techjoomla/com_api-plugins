<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
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
		$photo_obj = new EasySocialApiUploadHelper();
		$addphoto= $photo_obj->addPhotoAlbum($userid,$album_id);				
		$album->params=$addphoto;
		return $album;		
	}
}
