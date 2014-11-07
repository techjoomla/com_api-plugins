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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/friends.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/avatars.php';

class EasysocialApiResourceFriends extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getFriends());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->getFriends());
	}
	//function use for get friends data
	function getFriends()
	{
		//init variable
		$app = JFactory::getApplication();
		$user = JFactory::getUser($this->plugin->get('user')->id);
		$userid = ($app->input->get('userid',0,'INT'))?$app->input->get('userid',0,'INT'):$app->input->post->get('userid',0,'INT');
		
		//$search = (isset($app->input->get('search','','STRING')))?$app->input->get('search','','STRING'):$app->input->post->get('search','','STRING');
		$search = $app->input->get('search','','STRING');
		
		if($userid == 0)
		$userid = $user->id;
		
		$frnd_mod = new EasySocialModelFriends();
		
		//if search word present then search user as per term and given id
		if(empty($search))
		{
			$ttl_list = $frnd_mod->getFriends($userid); 
	    }
	    else
	    {
			$ttl_list = $frnd_mod->search($userid,$search,'username');
		}
		
	    $frnd_list = $this->basefrndObj($ttl_list);
    
	    //get other data
	    foreach($frnd_list as $ky=>$lval)
	    {	
			//get mutual friends of given user
			if($userid != $user->id)
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($userid,$lval->id);
				$lval->isFriend = $frnd_mod->isFriends($userid,$lval->id);
				//$lval->mutual_frnds = $frnd_mod->getMutualFriends($userid,$lval->id);
			}
			else
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($userid,$lval->id);
				$lval->isFriend = true;
			} 
		}

		return( $frnd_list );
	}
	
	//format friends object into required object
	function basefrndObj($data=null)
	{
		if($data==null)
		return 0;
				
		
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
			
			$list[] = $obj;
		}
		
		return $list;
		
	}
	
	

}
