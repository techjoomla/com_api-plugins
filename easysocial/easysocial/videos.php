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

defined('_JEXEC') or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;


require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/videos.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/components/com_easysocial/controllers/videos.php';

/**
 * API class EasysocialApiResourceProfile
 *
 * @since  1.0
 */
class EasysocialApiResourceVideos extends ApiResource
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
		$this->getVideos();
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$app = Factory::getApplication();
		$type = $app->input->get('source', 'upload', 'STRING');
		$result = $this->uploadVideos($type);
		$this->plugin->setResponse($result);
	}

	/**
	 * Method getVideos
	 *
	 * @return object videos and Categories
	 *
	 * @since 1.0
	 */
	private function getVideos()
	{
		$log_user	=	$this->plugin->get('user')->id;
		$model		=	ES::model('Videos');

		$options = array();
		$data = array();

		// Response object
		$res = new stdClass;
		$res->empty_message = '';

		$app = Factory::getApplication();
		$video_id = $app->input->get('video_id', 0, 'INT');

		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 10, 'INT');
		$filter = $app->input->get('filter', '', 'STRING');
		$categoryid = $app->input->get('categoryid', 0, 'INT');
		$sort = $app->input->get('sort', 'latest', 'STRING');

		$ordering = $this->plugin->get('ordering', '', 'STRING');

		$mapp = new EasySocialApiMappingHelper;
		$cats = $model->getCategories();

		$model->setUserState('limitstart', $limitstart);
		$options = array('limitstart' => $limitstart, 'limit' => $limit, 'sort' => $sort, 'filter' => $filter,
		'category' => $categoryid, 'state' => SOCIAL_STATE_PUBLISHED, 'ordering' => $ordering);

		if ($video_id)
		{
			$data[] = $this->getVideoDetails($video_id);
		}
		else
		{
			if ($options['limitstart'] <= $model->getTotalVideos($options))
			{
				$data = $model->getVideos($options);
			}
		}

		$all_videos = $mapp->mapItem($data, 'videos', $log_user);

		foreach ($cats as $k => $row)
		{
			$row->uid = $row->user_id;
		}

		$res->result->video = $mapp->mapItem($data, 'videos', $log_user);
		$res->result->categories = $mapp->categorySchema($cats);

		// $dataObj->results['length'] = count($dataObj->results['video']);
		if (!count($res->result->video))
		{
			$res->result->video = [];
			$res->empty_message = Text::_('COM_EASYSOCIAL_VIDEOS_EMPTY_MESSAGE');
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method Getting friends data
	 *
	 * @param   integer  $vid  video id
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function getVideoDetails($vid = 0)
	{
		if ($vid)
		{
			$table = ES::table('Video');
			$table->load($vid);

			$video = ES::video($table->uid, $table->type, $table);
			$video->hit();

			return $video;
		}

		return 0;
	}

	/**
	 * Upload video in throught api
	 *
	 * @param   object  $type  type
	 * 
	 * @return object error message and code
	 *
	 * @since 1.0
	 */
	private function uploadVideos($type)
	{
		$app = Factory::getApplication();
		$res = new stdClass;
		$es_config = ES::config();

		$id = $app->input->get('id', 0, 'int');
		$action = $app->input->get('action', '', 'STRING');
		$table = ES::table('Video');
		$table->load($id);

		$video = ES::video($table->uid, $table->type, $table);
		$user = Factory::getUser();

		// $isAdmin = $user->authorise('core.admin');
		// Get the callback url
		$callback = $app->input->get('return', '', 'default');

		if ($action == "featured")
		{
			$res->result->state = $video->setFeatured();
			$res->result->success = true;
			$res->result->message = ($result->state) ? (Text::_('PLG_API_EASYSOCIAL_VIDEO_FEATURED_SUCCESS')) :
			Text::_('PLG_API_EASYSOCIAL_VIDEO_FEATURED_FAIL');

			return $res;
		}
		elseif ($action == "unfeatured")
		{
			$res->result->state = $video->removeFeatured();
			$res->result->success = true;
			$res->result->message = ($result->state) ? (Text::_('PLG_API_EASYSOCIAL_VIDEO_UNFEATURED_SUCCESS')) :
			Text::_('PLG_API_EASYSOCIAL_VIDEO_UNFEATURED_FAIL');

			return $res;
		}
		elseif ($action == "delete")
		{
			$res->result->state = $video->delete();
			$res->result->success = true;
			$res->result->message = Text::_('PLG_API_EASYSOCIAL_VIDEO_DELETED_SUCCESS');

			return $res;
		}
		elseif ($action == "edit")
		{
			$video = ES::table('Video');
			$video->load($id);

			return $video;
		}
		elseif ($action == "update")
		{
			$video = ES::table('Video');
			$video->load($id);
			$video->description = ($video->description == 'undefined') ? ($video->description = '') : $video->description;
			$videoEdit = ES::video($video);

			// Save the video
			$post['category_id'] = $app->input->get('category_id', 0, 'INT');
			$post['uid'] = $app->input->get('uid', 0, 'INT');
			$post['title'] = $app->input->get('title', '', 'STRING');
			$post['description'] = $app->input->get('description', '', 'STRING');
			$post['link'] = $app->input->get('path', '', 'STRING');
			$post['tags'] = $app->input->get('tags', '', 'ARRAY');
			$post['location'] = $app->input->get('location', '', 'STRING');
			$post['privacy'] = $app->input->get('privacy', '', 'STRING');

			$state = $videoEdit->save($post);

			$res->result->success = 1;
			$res->result->message = Text::_('COM_EASYSOCIAL_VIDEOS_UPDATED_SUCCESS');

			return $res;
		}
	}
}
