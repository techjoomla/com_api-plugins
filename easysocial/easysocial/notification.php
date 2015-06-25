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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/tables/friend.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/friends.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceNotification extends ApiResource
{
	public function get()
	{	 
	 $this->plugin->setResponse($this->get_data());
	}
	public function post()
	{
	 $this->plugin->setResponse($this->friend_add_remove());
	}
	
	public function friend_add_remove()
	{		
		$app = JFactory::getApplication();
		$flag = $app->input->get('flag',NULL,'STRING');
						
		if($flag =='reject')
		{	
			$result1 = $this->removefriend();
			return $result1;			
		}
		else if($flag =='accept')		
		{			
			$result2 = $this->addfriend();
			return $result2;
		}		
		else
		return false;
	}
	public function removefriend()
	{
		$app = JFactory::getApplication();
		//getting target id and user id.
		$user = $app->input->get('user_id',0,'INT');
		$target = $app->input->get('target_id',0,'INT');
		//$log_user = JFactory::getUser($this->plugin->get('user')->id);		
		$friend	= FD::table( 'Friend' );
		$friend->actor_id  = $user;
		$friend->target_id = $target;
		//loading friend model for getting id.
		$friendmodel = FD::model( 'Friends' );
		$result = $friendmodel->getPendingRequests($user); 
		$state = SOCIAL_FRIENDS_STATE_FRIENDS;	
		 foreach($result as $r)
		 {
			  			
			 if( $r->actor_id == $target && $r->target_id == $user)
			 {  
				$friend->id = $r->id;
				break;				
			 }
			 else
			 continue;			  
		 }						
		//~ foreach( $result as $getid )
		//~ {
		 //~ $friend->id = $getid->id;		
		//~ }
		$status = $friendmodel->isFriends($user,$target,$state);
		if(!$status)
		{//final call to reject friend request.	
		$final = $friend->reject();
		}
		else
		{
			return false;
		}
		return $final;		
	 }	
	public function addfriend()
	{		
		$app = JFactory::getApplication();		
		$user = $app->input->get('user_id',0,'INT');
		$target = $app->input->get('target_id',0,'INT');		
		$friend	= FD::table( 'Friend' );		
		// Set the state and ensure that the state is both friends.
		//$friend->state = 'SOCIAL_FRIENDS_STATE_FRIENDS';		
		$friend->actor_id = $user;
		$friend->target_id = $target;
		$state = SOCIAL_FRIENDS_STATE_FRIENDS;
		$friendmodel = FD::model( 'Friends' );
		$result = $friendmodel->getPendingRequests($user);		
		 foreach($result as $r)
		 {
			  			
			 if( $r->actor_id == $target && $r->target_id == $user)
			 {  
				$friend->id = $r->id;
				break;				
			 }
			 else
			 continue;			  
		 }		
		//~ foreach( $result as $getid )
		//~ {		 	
		 //~ $friend->id = $getid->id;		
		//~ }
		$status = $friendmodel->isFriends($user,$target,$state);
		if(!$status)
		{						
		$final=$friend->approve();					
		}
		else
		{
			return false;
		}	
		return $final;	
	}	
	public function get_data()
	{
		$data = array();
		$data['messagecount'] = $this->get_message_count();
		$data['message'] = $this->get_messages();
		$data['notificationcount'] = $this->get_notification_count();		
		$data['notifications'] = $this->get_notifications();
		$data['friendcount'] = $this->get_friend_count();
		$data['friendreq'] = $this->get_friend_request();				 
		return $data;		
	}	
	public function get_friend_request()
	{
		 $app = JFactory::getApplication();
		 $uid = $app->input->get('uid',0,'INT');
		 $object = new  EasySocialModelFriends();
		 $result = $object->getPendingRequests($uid);
		 return $result;			
	}	
	public function get_friend_count()
	{
		$app = JFactory::getApplication();
		$uid = $app->input->get('uid',0,'INT');
		$model 	= FD::model( 'Friends' );
		$total 	= $model->getTotalRequests($uid);
		return $total;
	}	
	public function get_message_count()
	{
		$app = JFactory::getApplication();
		$uid = $app->input->get('uid',0,'INT');
		$model 	= FD::model( 'Conversations' );
		$total 	= $model->getNewCount($uid,'user');
		return $total;		
	}	
	public function get_notification_count()
	{
		$app = JFactory::getApplication();		
		$uid = $app->input->get('uid',0,'INT');		
		$options = array(
						'unread' => true,
						'target' => array('id' => $uid, 'type' => SOCIAL_TYPE_USER)
					);						
		$model 	= FD::model( 'Notifications' );
		$total 	= $model->getCount($options);
		return $total;
	}		
	public function get_messages()
	{
			// Get the current logged in user		
			$app = JFactory::getApplication();
			$uid = $app->input->get('uid',0,'INT');			
			$maxlimit = 0;
			// Get the conversations model
			$model = FD::model( 'Conversations' );
			// We want to sort items by latest first
			$options = array( 'sorting' => 'lastreplied', 'maxlimit' => $maxlimit );
			// Get conversation items.
			$conversations	= $model->getConversations( $uid , $options );
			return $conversations;		
	}
		
	public function get_notifications()
	{
		  // Load up the notifications library
		   $app = JFactory::getApplication();
		   $uid = $app->input->get('uid',0,'INT');	   	 
		   $notification = FD::notification();
		   $options = array('target_id' => $uid,
							'target_type' => SOCIAL_TYPE_USER,
							'unread' => true );			
			$items = $notification->getItems($options);
			// Mark all items as read if auto read is enabled.
			// Load up our model
			//$modell = FD::model('Notifications');
			//$items = $modell->getItems($options);		
			// Mark all notification as read
			//~ if ($state == 'read') 
			//~ {			
				//~ $result = $modell->setAllState(SOCIAL_NOTIFICATION_STATE_READ);		
				//~ return $result;
			//~ }	
			return $items;		
	}		
}
