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

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceNewsfeed
 *
 * @since  1.0
 */
class EasysocialApiResourceNewsfeed extends ApiResource
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
		$this->getStream();
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
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_EASYSOCIAL_USE_GET_METHOD_MESSAGE');
		$this->plugin->setResponse(null);
	}

	/**
	 * Method function use for get stream data
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function getStream()
	{
		// Init variable
		$app		=	JFactory::getApplication();

		// Code for get non sef urls
		$jrouter		=	JFactory::getApplication()->getRouter();
		$jrouter->setMode(JROUTER_MODE_RAW);
		$log_user		=	JFactory::getUser($this->plugin->get('user')->id);
		$group_id		=	$app->input->get('group_id', 0, 'INT');
		$event_id		=	$app->input->get('event_id', 0, 'INT');
		$page_id		=	$app->input->get('page_id', 0, 'INT');
		$view			=	$app->input->get('view', 'dashboard', 'STRING');
		$id				=	$this->plugin->get('user')->id;
		$target_user	=	$app->input->get('target_user', 0, 'INT');
		$limit			=	$app->input->get('limit', 10, 'INT');
		$startlimit		=	$app->input->get('limitstart', 0, 'INT');
		$filter			=	$app->input->get('filter', 'everyone', 'STRING');

		// Get tag
		$tag			=	$app->input->get('tag', '', 'STRING');
		$config			=	JFactory::getConfig();
		$sef			=	$config->set('sef', 0);

		$res = new stdClass;

		// Map object
		$mapp			=	new EasySocialApiMappingHelper;

		// If user id is not passed in, return logged in user
		if (!$target_user)
		{
			$target_user	=	$id;
		}

		// Get the stream library
		$stream 		=	FD::stream();

		if ($event_id)
		{
			$options	=	array('clusterId' => $event_id, 'clusterType' => SOCIAL_TYPE_EVENT, 'startlimit' => $startlimit, 'limit' => $limit);
		}
		elseif ($group_id)
		{
			$options	=	array('clusterId' => $group_id, 'clusterType' => SOCIAL_TYPE_GROUP, 'startlimit' => $startlimit, 'limit' => $limit);
		}
		elseif ($page_id)
		{
			$options	=	array('clusterId' => $page_id, 'clusterType' => SOCIAL_TYPE_PAGE, 'startlimit' => $startlimit, 'limit' => $limit);
		}
		else
		{
			$options	=	array('userId' => $target_user, 'startlimit' => $startlimit, 'limit' => $limit);
		}

		if ($target_user == $id)
		{
			switch ($filter)
			{
				case 'everyone':
					$options['guest']		=	true;
					$options['ignoreUser']	=	true;
					$options['view']		=	$view;
					break;

				case 'following':
				case 'follow':
					$options['type']	=	'follow';
					break;
				case 'bookmarks':
					$options['guest']	=	true;
					$options['type']	=	'bookmarks';
				case 'me':
					$options['view']	=	'profile';
					break;
				case 'hashtag':
					$options['tag']	=	$tag;
					break;
				case 'sticky':
					$options['type']	=	'sticky';
					break;
				default:
					$options['context']	=	$filter;
					$options['userId']	=	$id;
					$options['view']	=	'dashboard';
					break;
			}
		}

		$stream->get($options);

		$result	=	$stream->toArray();

		if (!count($result) || !is_array($result))
		{
			$res->result = array();
			$res->empty_message	=	JText::_('COM_EASYSOCIAL_STREAM_NO_STREAM_ITEM');
			$this->plugin->setResponse($res);
		}
		else
		{
			$res->result	=	$mapp->mapItem($result, 'stream', $target_user);
			$res->empty_message = '';
		}

		$jrouter->setMode(JROUTER_MODE_SEF);
		$this->plugin->setResponse($res);
	}
}
