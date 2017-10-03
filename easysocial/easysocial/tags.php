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

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/components/com_easysocial/controllers/videos.php';

/**
 * API class EasysocialApiResourceTerms
 *
 * @since  1.0
 */
class EasysocialApiResourceTags extends ApiResource
{
	/**
	 * get.
	 *
	 * @see        JController
	 * @since      1.0
	 * @return true or null
	 */
		public function get()
		{
			$this->getTags();
		}

	/**
	 * get videos throught api
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getTags()
	{
		$app = JFactory::getApplication();
		$log_user	=	$this->plugin->get('user')->id;

		// Get the video
		$videoid = $app->input->get('video_id', 0, 'INT');
		$video = ES::video();
		$video->load($videoid);

		$model = ES::model('Tags');
		$tag_peoples = $model->getTags($videoid, SOCIAL_TYPE_VIDEO);
		$mapp = new EasySocialApiMappingHelper;

		if ($tag_peoples)
		{
			foreach ( $tag_peoples as $tusr )
			{
				$tusr->target_user_obj[] = $mapp->mapItem($tusr->item_id, 'profile', $log_user);
			}
		}

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		$res->result = $tag_peoples;
		$this->plugin->setResponse($res);
	}

	/**
	 * friends tags
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		// Check for request forgeries
		// ES::checkToken();

		$app = JFactory::getApplication();

		// Get the user id's.
		$friends_tags = $app->input->get('friends_tags', null, 'ARRAY');

		// Get the video
		$cluster = $app->input->get('cluster_id', null, 'INT');

		$table = ES::table('Video');
		$table->load($cluster);
		$video = ES::video($table->uid, $table->type, $table);

		// Insert the user tags
		$tags = $video->insertTags($friends_tags);

		$video = ES::video();
		$video->load($cluster);
		$model = ES::model('Tags');
		$tag_peoples = $model->getTags($videoid, SOCIAL_TYPE_VIDEO);

		$this->plugin->setResponse($tag_peoples);
	}
}
