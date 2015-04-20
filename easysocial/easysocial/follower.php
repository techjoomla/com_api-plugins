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

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceFollower extends ApiResource
{

	public function get()
	{
		$this->plugin->setResponse($this->getFollowers());
	}

	public function post()
	{
		$this->plugin->setResponse('Post method not allowed for this api, use another method.');
	}
	
	/*
	 *Function for delete friend from list 
	 */
	public function delete()
	{
		$app = JFactory::getApplication();
		$target_user = $app->input->get('target_userid',0,'INT');

		$log_user 	= $this->plugin->get('user')->id;
		//$log 	= FD::user( $frnd_id );
		// Loads the followers record
		$follower 	= FD::table( 'Subscription' );
		$follower->load( array( 'uid' => $target_user , 'type' => 'user.user' , 'user_id' => $log_user ) );

		$res = new stdClass();

		// Delete the record
		$res->result 	= $follower->delete();

		if( $res->result == true )
		{
			 $res->status = 1;
			 $res->message = 'User remove from followers';
		}
		else
		{
			$res->status = 0;
			$res->message = 'Unable to remove user from follower list';
		}
		
		$this->plugin->setResponse($res);
	}
	
	//function use for get friends data
	function getFollowers()
	{
		//init variable
		$app = JFactory::getApplication();
		$user = $this->plugin->get('user')->id;
		$target_user = $app->input->get('target_user',0,'INT');
		$type = $app->input->get('type','follower','STRING');
		
		$options = array();
		$options['limitstart'] = $app->input->get('limitstart',0,'INT');
		$options['limit'] = $app->input->get('limit',0,'INT');

		// Load friends model.
		$foll_model 	= FD::model( 'Followers' );
		$frnd_mod 	= FD::model( 'Friends' );
		
		if(!$target_user)
		$target_user = $user;
		
		$data = array();
		$mapp = new EasySocialApiMappingHelper();

		$raw_followers = array();
		
		if( $type == 'following' )
		{
			$raw_followers 	= $foll_model->getFollowing( $target_user, $options );
		}
		else
		{
			$raw_followers 	= $foll_model->getFollowers( $target_user, $options );
		}

	    //$frnd_list = $this->basefrndObj($ttl_list);
	    $fllowers_list = $mapp->mapItem( $raw_followers,'user',$user );

	    //get other data
	    foreach($fllowers_list as $ky=>$lval)
	    {	
			$lval->mutual = $frnd_mod->getMutualFriendCount($user,$lval->id);
			$lval->isFriend = $frnd_mod->isFriends($users,$lval->id);
		}
		
		$data['data'] = $fllowers_list; 

		return( $data);
		
	}
}
