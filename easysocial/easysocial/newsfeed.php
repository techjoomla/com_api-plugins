<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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
		$app = JFactory::getApplication();
		
		$log_user = JFactory::getUser($this->plugin->get('user')->id);

		$group_id = $app->input->get('group_id', 0, 'INT');

        $id = $this->plugin->get('user')->id;
        
        $userId = $app->input->get('target_user', 0, 'INT');
        
        $limit = $app->input->get('limit', 10, 'INT');
        $startlimit = $app->input->get('limitstart', 0, 'INT');
        
        $filter = $app->input->get('filter', 'everyone', 'STRING');
		
		//map object
		$mapp = new EasySocialApiMappingHelper();

        // If user id is not passed in, return logged in user
        if (!$userId) {
            $userId = $id;
        }

		// Get the stream library
		$stream 	= FD::stream();

		$options = array('userId' => $userId, 'startlimit' => $startlimit, 'limi' => $limit);
		
		if($group_id)
		{
			$options = array('clusterId' 	=> $group_id, 'clusterType' => SOCIAL_TYPE_GROUP, 'startlimit' => $startlimit, 'limit' => $limit);
		}
		
		switch($filter) {
			case 'everyone':
				$options['guest'] = true;
				$options['ignoreUser'] = true;
				break;

			case 'following':
			case 'follow':
				$options['type'] = 'follow';
				break;
			case 'bookmarks':
				$options['guest'] = true;
				$options['type'] = 'bookmarks';
			case 'me':
				// nohting to set
				break;
			case 'hashtag':
				$tag = '';
				$options['tag'] = $tag;
				break;
			default:
				$options['context'] = $filter;
				break;
		}

		// $stream->get(array('userId' => $userId, 'startlimit' => $startlimit, 'limit' => $limit, 'type' => $filter));
		$stream->get($options);

		$result 	= $stream->toArray();

		$data	= array();
	
		// Set the url to this listing
		//$data->url = FRoute::stream();

		$data11 = $mapp->mapItem($result,'stream',$userId);

		return $data11;
	}
	
	/*
	//format friends object into required object
	function baseGrpObj($data=null)
	{
		if($data==null)
		return 0;

		$user = JFactory::getUser($this->plugin->get('user')->id);

		$list = array();
		
		$grp_obj = FD::model('Groups');
	
		foreach($data as $k=>$node)
		{

			$obj = new stdclass;
			$obj->id = $node->id;
			$obj->title = $node->title;
			$obj->description = $node->description;
			$obj->hits = $node->hits;
			$obj->state = $node->state;
			$obj->created_date = $node->created;
			
			//get category name
			$category 	= FD::table('GroupCategory');
			$category->load($node->category_id);
			$obj->category_id = $node->category_id;
			$obj->category_name = $category->get('title');
			
			$obj->created_by = $this->getActor($node->creator_uid);
			$obj->creator_name = JFactory::getUser($node->creator_uid)->username;
			$obj->type = ($node->type == 1 )?'Private':'Public';
			
			foreach($node->avatars As $ky=>$avt)
			{
				$avt_key = 'avtar_'.$ky;
				$obj->$avt_key = JURI::root().'media/com_easysocial/avatars/group/'.$node->id.'/'.$avt;
			}
			
			//$obj->members = $node->members;
			$obj->members = $grp_obj->getTotalMembers($node->id);
			//$obj->cover = $grp_obj->getMeta($node->id);

			$news_obj = new EasySocialModelGroups();
			$news = $news_obj->getNews($node->id); 
			
			$list[] = $obj;
		}
		return $list;
	}
	*/
}
