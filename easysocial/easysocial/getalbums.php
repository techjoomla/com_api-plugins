<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugins
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

/**
 * API class EasysocialApiResourceGetalbums
 *
 * @since  1.0
 */
class EasysocialApiResourceGetalbums extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->get_albums();
	}

	/**
	 * Method Get user album as per id / login user
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get_albums()
	{
		$app	=	JFactory::getApplication();

		// Getting log_user
		$log_user	=	$this->plugin->get('user')->id;

		// Accepting user details.
		$uid		=	$app->input->get('uid', 0, 'INT');
		$type		=	$app->input->get('type', 0, 'STRING');
		$mapp		=	new EasySocialApiMappingHelper;

		// Accepting pagination values.
		$limitstart	=	$app->input->get('limitstart', 0, 'INT');
		$limit		=	$app->input->get('limit', 10, 'INT');

		// Taking values in array for pagination of albums.
		// $mydata['limitstart']=$limitstart;

		$mydata['excludeblocked']	=	1;
		$mydata['pagination']		=	1;

		// $mydata['limit']			=	$limit;
		$mydata['privacy']			=	true;
		$mydata['order']			=	'a.assigned_date';
		$mydata['direction']		=	'DESC';

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		// Creating object and calling relatvie method for data fetching.
		$obj = new EasySocialModelAlbums;

		$albums = $obj->getAlbums($uid, $type, $mydata);

		// Use to load table of album.
		$album	=	FD::table('Album');

		foreach ($albums as $album)
		{
			if ($album->cover_id)
			{
				$album->load($album->id);
			}

			$album->cover_featured	=	$album->getCover('featured');
			$album->cover_large		=	$album->getCover('large');
			$album->cover_square	=	$album->getCover('square');
			$album->cover_thumbnail	=	$album->getCover('thumbnail');
		}

		// Getting count of photos in every albums.
		foreach ($albums as $alb)
		{
			$alb->count = $obj->getTotalPhotos($alb->id);
		}

		$all_albums	=	$mapp->mapItem($albums, 'albums', $log_user);
		$output		=	array_slice($all_albums,  $limitstart, $limit);

		if (count($output) == 0)
		{
			$res->empty_message = JText::_('COM_EASYSOCIAL_NO_ALBUM_AVAILABLE');
		}
		else
		{
			$res->result		=	$output;
		}

		$this->plugin->setResponse($res);
	}
}
