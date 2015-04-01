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

class EasysocialApiResourceGroups extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getGroups());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->getGroups());
	}
	//function use for get friends data
	function getGroups()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		$db = JFactory::getDbo();
		
		$nxt_lim = 20;
	
		$search = $app->input->get('search','','STRING');
		//$group_id = $app->input->get('id',0,'INT'); 
		
		$userid = $log_user->id;
		$mapp = new EasySocialApiMappingHelper();
		
		$filters = array();
		$filters['category'] = $app->input->get('category',0,'INT');
		$filters['uid'] = $app->input->get('user_id',0,'INT');
		$filters['types'] = $app->input->get('type',0,'INT');
		$filters['state'] = $app->input->get('state',0,'INT');
		
		$filters['featured'] = $app->input->get('featured',false,'BOOLEAN');
		$filters['mygroups'] = $app->input->get('mygroups',false,'BOOLEAN');
		$filters['invited'] = $app->input->get('invited',false,'BOOLEAN');
		
		$filters['limit'] = $app->input->get('limit',0,'INT');
		
		$model = FD::model('Groups');
		$options = array('state' => SOCIAL_STATE_PUBLISHED,'ordering' => 'latest');
		$groups = array();

		if($filters['featured'])
		{
			$options['featured']	= true;
			$featured = $model->getGroups($filters);
			$featured_grps = $mapp->mapItem($featured,'group',$log_user->id);
			
			if(count($featured_grps) > 0 && $featured_grps != false)
			{
				return $featured_grps;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			if($filters['mygroups'])
			{
				$options['uid'] = $log_user->id;
				$options['types'] = 'all';
			}
			
			if($filters['invited'])
			{
				$options['invited'] = $log_user->id;
				$options['types'] = 'all';
			}
			
			if ($filters['category'])
			{
				$options['category'] = $categoryId;
			}
	
			$groups = $model->getGroups($options);

			//$groups = $this->baseGrpObj($groups);
			$groups = $mapp->mapItem($groups,'group',$log_user->id);
		}

		return( $groups );
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
	
	//to get actor object
	function getActor($id=0)
	{
		if($id==0)
		return 0;
		
		$user = JFactory::getUser($id);
		
		$obj = new stdclass;
		
		$obj->id = $user->id; 
		$obj->username = $user->username; 
		$obj->url = ''; 
		
		return $obj;
	}
}
