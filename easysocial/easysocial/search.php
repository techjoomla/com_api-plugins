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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/search.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/avatars.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/friends.php';
require_once JPATH_ROOT . '/components/com_finder/models/search.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceSearch extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getSearch());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->getSearch());
	}
	//function use for get friends data
	function getSearch()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
	
		$nxt_lim = 20;
	
		$search = $app->input->get('search','','STRING');
		$nxt_lim = $app->input->get('next_limit',0,'INT');
		$mapp = new EasySocialApiMappingHelper();
		
		$res = new stdClass;
		
		if(empty($search))
		{
			$res->status = 0;
			$res->message = 'Empty searchtext';
			return $res;
		}
		
		$userid = $log_user->id;
	
		$serch_obj = new EasySocialModelSearch();
			
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$frnd_obj = new EasySocialModelFriends();
		
		$query->select($db->quoteName(array('su.user_id')));
		$query->from($db->quoteName('#__social_users','su'));
		$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('su.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		//->where(($db->quoteName('u.username') . ' LIKE '. $db->quote('\'% '.$search.'%\'') ).'OR' .( $db->quoteName('u.name') . ' LIKE '. $db->quote('\'%'.$search.'%\'') ).'OR'.( $db->quoteName('u.email') . ' LIKE '. $db->quote('\'%'.$search.'%\'')))

		if(!empty($search))
		{
			$query->where("(u.username LIKE '%".$search."%' ) OR ( u.name LIKE '%".$search."%') OR ( u.email LIKE '%".$search."%')");
		}
		
		$query->order($db->quoteName('u.id') .'ASC');

		$db->setQuery($query);
		$tdata = $db->loadObjectList();

		$susers = array();
		foreach($tdata as $ky=>$val)
		{		
			$susers[]   = FD::user($val->user_id);
		}
		
		$base_obj = $mapp->mapItem($susers,'user',$log_user->id);

		$list['user'] = $this->createSearchObj( $base_obj );
		
		//for group
		$query1 = $db->getQuery(true);
		$query1->select($db->quoteName(array('cl.id')));
		$query1->from($db->quoteName('#__social_clusters','cl'));
		
		if(!empty($search))
		{
			$query1->where("(cl.title LIKE '%".$search."%' )");
		}
		
		$query1->order($db->quoteName('cl.id') .'ASC');

		$db->setQuery($query1);
		$gdata = $db->loadObjectList();
		
		
		$grp_model = FD::model('Groups');
		$group = array();
		foreach($gdata as $grp)
		{
			$group[] = FD::group($grp->id);
		}
		
		$list['group'] = $mapp->mapItem($group,'group',$log_user->id);
		
		if(empty($list['group']))
               	{
                       $ret_arr = new stdClass;
                       $ret_arr->status = false;
                       $ret_arr->message = "No group found in search";
                       $list['group']= $ret_arr;
               	}


		return( $list );
		
	}
	
	//format friends object into required object
	function createSearchObj($data=null)
	{
		if( $data == null )
		{
			$ret_arr = new stdClass;
			$ret_arr->status = false;
			$ret_arr->message = "No user found in search";
			
			return $ret_arr;
		}
	
		
		$user = JFactory::getUser($this->plugin->get('user')->id);
		$frnd_mod = new EasySocialModelFriends();
		$list = array();

		$options[ 'state' ]	= SOCIAL_FRIENDS_STATE_PENDING;
		$options[ 'isRequest' ]	= true;		
		$req=$frnd_mod->getFriends( $user->id,$options );	
		$myarr=array();
		if(!empty($req))
		{
			foreach($req as $ky=>$row)	
			{
				$myarr[]= $row->id;
			}
		}

		foreach($data as $k=>$node)
		{
			if($node->id != $user->id)
			{
				
				$node->mutual = $frnd_mod->getMutualFriendCount($user->id,$node->id);
				$node->isFriend = $frnd_mod->isFriends($user->id,$node->id);
				$node->approval_pending = $frnd_mod->isPendingFriends($user->id,$node->id);
				if(in_array($node->id,$myarr))
				{
					$node->isinitiator=true;
				}
				else
				{
					$node->isinitiator=false;
				}
				
				$list[] = $node;
			}
		}

		return $list;
		
	}
	
	

}
