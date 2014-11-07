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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/search.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/avatars.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/friends.php';
require_once JPATH_ROOT . '/components/com_finder/models/search.php';

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
	
		$search = $app->input->post->get('search','','STRING');
		$nxt_lim = $app->input->get('next_limit',0,'INT');
		
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
		
		$frnd_list = $this->basefrndObj($susers);

		return( $frnd_list );
		
	}
	
	//format friends object into required object
	function basefrndObj($data=null)
	{
		if($data==null)
		return 0;
		
		$user = JFactory::getUser($this->plugin->get('user')->id);		
		$frnd_mod = new EasySocialModelFriends();
		$list = array();
		foreach($data as $k=>$node)
		{
			$obj = new stdclass;
			$obj->id = $node->id;
			$obj->name = $node->name;
			$obj->username = $node->username;
			$obj->email = $node->email;
			
			//$obj->avatar = EasySocialModelAvatars::getPhoto($node->id);
			foreach($node->avatars As $ky=>$avt)
			{
				$avt_key = 'avtar_'.$ky;
				$obj->$avt_key = JURI::root().'media/com_easysocial/avatars/users/'.$node->id.'/'.$avt;
			}
			
			$obj->mutual = $frnd_mod->getMutualFriendCount($user->id,$node->id);
			$obj->isFriend = $frnd_mod->isFriends($user->id,$node->id);
			
			$list[] = $obj;
		}
		
		return $list;
		
	}
	
	

}
