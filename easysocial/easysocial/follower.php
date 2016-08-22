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
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/model.php';

class EasysocialApiResourceFollower extends ApiResource
{

	public function get()
	{
		$this->plugin->setResponse($this->getFollowers());
	}

	public function post()
	{
		$this->plugin->setResponse($this->follow());
	}
	
	/*
	 *Function for follow user 
	 */
	public function follow()
	{
		$app = JFactory::getApplication();
		$target_user = $app->input->get('target_user',0,'INT');
		$type = $app->input->get('type','user','STRING');
		$group = $app->input->get('group','user','STRING');
		$log_user 	= $this->plugin->get('user')->id;
		$follow 	= $app->input->get('follow',1,'INT');
		
		$target 	= FD::user( $target_user );
		
		$res = new stdClass();
		if( $target_user == $log_user )
		{
			$res->success = 0;
			$res->message = JText::_( 'PLG_API_EASYSOCIAL_CANT_FOLLOW_YOURSELF_MESSAGE' );
		}
		
		// Load subscription table.
		$subscription 	= FD::table('Subscription');

		// Get subscription library
		$subscriptionLib 	= FD::get('Subscriptions');
		// Determine if the current user is already a follower
		$isFollowing 	= $subscriptionLib->isFollowing( $target_user , $type , $group , $log_user );
		
		$subscription->uid 		= $target_user;
		$subscription->type 	= $type . '.' . $group;
		$subscription->user_id	= $log_user;
		$points = FD::points();

		if(!$isFollowing)
		{
			//$state 	= $subscription->store();
			if($subscription->store())
			{
				$state = $this->addbadges( $target,$log_user,$subscription->id );
		
				$res->success = 1;
				$res->message = JText::_( 'PLG_API_EASYSOCIAL_FOLLOWING_MESSAGE' ).$target->username;
				
				return $res;
			}
			else
			{
				$res->success = 0;
				$res->message = JText::_( 'PLG_API_EASYSOCIAL_UNABLE_TO_FOLLOW_MESSAGE' ).$target->username;
				return $res;
			}
			
		}
		else
		{
			if( $subscriptionLib->unfollow( $target_user, $type, $group, $log_user ) )
			{
				// @points: profile.unfollow
				// Assign points when user starts new conversation
				
				$points->assign( 'profile.unfollow' , 'com_easysocial' , $log_user);

				// @points: profile.unfollowed
				// Assign points when user starts new conversation
				$points->assign( 'profile.unfollowed' , 'com_easysocial' , $user->id );
				$res->success = 1;
				$res->message = JText::_( 'PLG_API_EASYSOCIAL_SUCCESSFULLY_UNFOLLW_MESSAGE' ).$target->username;
				
				return $res;
			}
			else
			{
				$res->success = 0;
				$res->message = JText::_( 'PLG_API_EASYSOCIAL_UNABLE_TO_UNFOLLW_MESSAGE' ).$target->username;
				
				return $res;
			}
		}
		
	}
	
	/*
	 *Function for add badges 
	 */
	public function addbadges( $user,$log_user,$sub_id )
	{
		$my 	= FD::user( $log_user );
		// @badge: followers.follow
		$badge 	= FD::badges();
		$badge->log( 'com_easysocial' , 'followers.follow' , $my->id , JText::_( 'COM_EASYSOCIAL_FOLLOWERS_BADGE_FOLLOWING_USER' ) );

		// @badge: followers.followed
		$badge->log( 'com_easysocial' , 'followers.followed' , $user->id , JText::_( 'COM_EASYSOCIAL_FOLLOWERS_BADGE_FOLLOWED' ) );

		// @points: profile.follow
		// Assign points when user follows another person
		$points = FD::points();
		$points->assign( 'profile.follow' , 'com_easysocial' , $my->id );

		// @points: profile.followed
		// Assign points when user is being followed by another person
		$points->assign( 'profile.followed' , 'com_easysocial' , $user->id );

		// check if admin want to add stream on following a user or not.
		$config = FD::config();
		if ($config->get( 'users.stream.following')) {
			// Share this on the stream.
			$stream 			= FD::stream();
			$streamTemplate		= $stream->getTemplate();

			// Set the actor.
			$streamTemplate->setActor( $my->id , SOCIAL_TYPE_USER );

			// Set the context.
			$streamTemplate->setContext( $sub_id , SOCIAL_TYPE_FOLLOWERS );

			// Set the verb.
			$streamTemplate->setVerb( 'follow' );

			$streamTemplate->setAccess( 'followers.view' );

			// Create the stream data.
			$stream->add( $streamTemplate );
		}

        // Set the email options
        $emailOptions   = array(
            'title'     	=> 'COM_EASYSOCIAL_EMAILS_NEW_FOLLOWER_SUBJECT',
            'template'		=> 'site/followers/new.followers',
            'actor'     	=> $my->getName(),
            'actorAvatar'   => $my->getAvatar(SOCIAL_AVATAR_SQUARE),
            'actorLink'     => $my->getPermalink(true, true),
            'target'		=> $user->getName(),
            'targetLink'	=> $user->getPermalink(true, true),
            'totalFriends'		=> $my->getTotalFriends(),
            'totalFollowing'	=> $my->getTotalFollowing(),
            'totalFollowers'	=> $my->getTotalFollowers()
        );


		$state 	= FD::notify('profile.followed' , array($user->id), $emailOptions, array( 'url' => $my->getPermalink(false, false, false) ,  'actor_id' => $my->id , 'uid' => $user->id ));
		
		return $state;
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
			 $res->message = JText::_( 'PLG_API_EASYSOCIAL_USER_REMOVE_MESSAGE' );
		}
		else
		{
			$res->status = 0;
			$res->message = JText::_( 'PLG_API_EASYSOCIAL_UNABLE_TO_REMOVE_USER_MESSAGE' );
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
		$options['limit'] = $app->input->get('limit',10,'INT');

		// Load friends model.
		$foll_model 	= FD::model( 'Followers' );
		$frnd_mod 	= FD::model( 'Friends' );
		
		//$main_mod = new EasySocialModel();

		//set limitstart
		//$main_mod->setUserState( 'limitstart' , $options['limitstart'] );
		$foll_model->setUserState( 'limitstart' , $options['limitstart'] );
		
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
