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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/friends.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/avatars.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceFriend extends ApiResource
{

	public function get()
	{
		$this->plugin->setResponse($this->getFriends());
	}

	public function post()
	{
		$this->plugin->setResponse($this->manageFriends());
	}
	
	/*
	 *Function for delete friend from list 
	 */
	public function delete()
	{
		$this->plugin->setResponse($this->deletefriend());

		/*$app = JFactory::getApplication();
		$frnd_id = $app->input->get('target_userid',0,'INT');

		$user 	= FD::user( $frnd_id );
		$user->approve();
		$res = new stdClass();

		$res->result = EasySocialModelUsers::deleteFriends($frnd_id);

		if( $res->result == true )
		{
			 $res->status = 1;
			 //$res->message = 'Freind deleted';
		}
		else
		{
			$res->status = 0;
			//$res->message = 'Unable to delete friend';
		}
		
		$this->plugin->setResponse($res);*/
	}

	public function deletefriend()
	{	
		$app = JFactory::getApplication();
		//get target user.
		$frnd_id = $app->input->get('target_userid',0,'INT');
		//getting log user.
		$log_user = $this->plugin->get('user')->id;
		$res = new stdClass();
		// Try to load up the friend table
		$friend_table	= FD::table( 'Friend' );
		//load user table.
		$state=$friend_table->loadByUser($log_user,$frnd_id);
		
		//API validations.
		if( !$state )
		{
			$res->status = 0;
			$res->message = 'Unable to delete friend';
			return $res;
		}			
		// Throw errors when there's a problem removing the friends
		if( !$friend_table->unfriend( $log_user ) )
		{			
			$res->status = 0;
			$res->message = 'Unable to delete friend';
		}
		else
		{
			$res->status = 1;
			$res->message = 'Friend deleted';
			return $res;
		}
	}
	
	//function use for get friends data
	function getFriends()
	{
		//init variable
		$app = JFactory::getApplication();
		$user = JFactory::getUser($this->plugin->get('user')->id);
		$userid = $app->input->get('target_user',$this->plugin->get('user')->id,'INT');
		
		$search = $app->input->get('search','','STRING');
		
		$mapp = new EasySocialApiMappingHelper();
		
		if($userid == 0)
		$userid = $user->id;
		
		$frnd_mod = new EasySocialModelFriends();
		
		// if search word present then search user as per term and given id
		if(empty($search))
		{
			$ttl_list = $frnd_mod->getFriends($userid); 
	    }
	    else
	    {
			$ttl_list = $frnd_mod->search($userid,$search,'username');
		}

	    //$frnd_list = $this->basefrndObj($ttl_list);
	    $frnd_list = $mapp->mapItem( $ttl_list,'user',$userid);

	    //get other data
	    foreach($frnd_list as $ky=>$lval)
	    {	
			//get mutual friends of given user
			/*if($userid != $user->id)
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($user->id,$lval->id);
				$lval->isFriend = $frnd_mod->isFriends($user->id,$lval->id);
				//$lval->mutual_frnds = $frnd_mod->getMutualFriends($userid,$lval->id);
			}
			else
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($userid,$lval->id);
				$lval->isFriend = $frnd_mod->isFriends($user->id,$lval->id);
			}*/

			$lval->mutual = $frnd_mod->getMutualFriendCount($user->id,$lval->id);
			$lval->isFriend = $frnd_mod->isFriends($user->id,$lval->id);
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
	
	//function use for get friends data
	function manageFriends()
	{
		//init variable
		$app = JFactory::getApplication();
		
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		
		$db = JFactory::getDbo();
		
		$frnd_id = $app->input->get('target_userid',0,'INT');

		$userid = $log_user->id;
		
		$res = new stdClass();
		
		if(!$frnd_id)
		{
			return 'Friend id not found';
		}

		$frnds_obj = new EasySocialModelFriends();
		
		$result = $frnds_obj->request($userid,$frnd_id);
		
		if($result->id)
		{
			$res->status = 1;
			/*$res->frnd_id = $frnd_id;
			$res->code = 200;
			$res->message = 'Request send';*/
		}
		else
		{
			$res->status = 0;
			/*$res->code = 403;
			$res->message = $result;*/
		}
		return $res;
		
	}

}
