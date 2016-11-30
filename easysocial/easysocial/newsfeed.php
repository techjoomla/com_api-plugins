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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceNewsfeed extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getStream());
	}

	public function post()
	{
		$this->plugin->setResponse($this->getGroups());
	}
	//function use for get stream data
	function getStream()
	{
		//init variable
		$app =  JFactory::getApplication();
		//code for get non sef urls 
		$jrouter = JFactory::getApplication()->getRouter();
		$jrouter->setMode(JROUTER_MODE_RAW);
	
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		$group_id = $app->input->get('group_id', 0, 'INT');
		$event_id = $app->input->get('event_id', 0, 'INT');
		$view = $app->input->get('view', 'dashboard', 'STRING');	

		$id = $this->plugin->get('user')->id;
		$target_user = $app->input->get('target_user', 0, 'INT');

		$limit = $app->input->get('limit', 10, 'INT');
		$startlimit = $app->input->get('limitstart', 0, 'INT');
		
		$filter = $app->input->get('filter', 'everyone', 'STRING');
		//get tag
		$tag = $app->input->get('tag', '', 'STRING');
		
		$config = JFactory::getConfig();
		$sef = $config->set('sef', 0);
	
	//map object
	$mapp = new EasySocialApiMappingHelper();

		// If user id is not passed in, return logged in user
		if (!$target_user) {
			$target_user = $id;
		}

	// Get the stream library
	$stream 	= FD::stream();
	$options = array('userId' => $target_user, 'startlimit' => $startlimit, 'limit' => $limit);
	$clusterType = SOCIAL_TYPE_GROUP;
	
	if($event_id)
	{
		$group_id = $event_id;
		$clusterType = SOCIAL_TYPE_EVENT;
	}
	
	if($group_id)
	{
		$options = array('clusterId' => $group_id, 'clusterType' => $clusterType, 'startlimit' => $startlimit, 'limit' => $limit);
	}
	
	if($target_user == $id )
	{
		switch($filter) {
			case 'everyone':
				$options['guest'] = true;
				$options['ignoreUser'] = true;
				$options['view'] = $view;
				break;

			case 'following':
			case 'follow':
				$options['type'] = 'follow';
				break;
			case 'bookmarks':
				$options['guest'] = true;
				$options['type'] = 'bookmarks';
			case 'me':
				$options['view'] =	$view;
				break;
			case 'hashtag':
				$options['tag'] = $tag;
				break;
			case 'sticky':
				$options['type'] = 'sticky';
				break;
			default:
				$options['context'] = $filter;
				$options['userId'] = $id;
				$options['view'] = 'dashboard';
				break;
			}
		}

		$stream->get($options);
		$result = $stream->toArray();
		$data	= array();
		if(!is_array($result))
		{
			return $data;
		}
		$data = $mapp->mapItem($result,'stream',$target_user);
		$jrouter->setMode(JROUTER_MODE_SEF);
		return $data;
	}
}
