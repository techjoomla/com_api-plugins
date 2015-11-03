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
		$avt_model = FD::model( 'Avatars' );
		$default = $avt_model->getDefaultAvatars(0,$type = SOCIAL_TYPE_PROFILES); 		
		//init variable
		$app = JFactory::getApplication();
		$user = JFactory::getUser($this->plugin->get('user')->id);
		$userid = $app->input->get('target_user',0,'INT');
		$filter = $app->input->get('filter',NULL,'STRING');
		$search = $app->input->get('search','','STRING');
		$limit = $app->input->get('limit',10,'INT');
		$limitstart = $app->input->get('limitstart',0,'INT');		
		$options['limit']=$limit;
		$options['limitstart']=$limitstart;
		$mssg='';
		$flag = 1;
		$mapp = new EasySocialApiMappingHelper();
		
		if($userid == 0)
		$userid = $user->id;
		
		$frnd_mod = FD::model( 'Friends' );
		$frnd_mod->setState('limit',$limit);
		$frnd_mod->setState('limitstart',$limitstart);
		
		$ttl_list = array();
		switch($filter)
		{			
			case 'pending': //get the total pending friends.
							$options[ 'state' ]	= JText::_('SOCIAL_FRIENDS_STATE_PENDING');
							$mssg="You have no pending request";																					
			break;			
			case 'all':		//getting all friends
							$options[ 'state' ]	= JText::_('SOCIAL_FRIENDS_STATE_FRIENDS');
							$mssg="You have no friends";
			break;			
			case 'request':	//getting sent requested friends.
							$options[ 'state' ]	= JText::_('SOCIAL_FRIENDS_STATE_PENDING');
							$options[ 'isRequest' ]	= true;							
							$mssg="You have no sent request";
			break;
			
			case 'suggest': //getting suggested friends							
							$sugg_list = $frnd_mod->getSuggestedFriends($userid);
							foreach($sugg_list as $sfnd)
							{
								$ttl_list[] = $sfnd->friend;
							}
							if(empty($ttl_list))
								$flag=1;
							else 
								$flag=0;
							$mssg="You have no suggetions";  							
			break;						
			case 'invites': //getiing invited friends
							  $invites['data'] = $frnd_mod->getInvitedUsers($userid);
							  $mssg="You have no invitations";
							  if(empty($invites['data']))
							  {
								$invites['data']['message']=$mssg;
								$invites['data']['status']=false;
							  }
							  return $invites;
			break;		
		}
	
		// if search word present then search user as per term and given id
		if(empty($search) && empty($ttl_list) && $flag!=1)
		{
			$ttl_list = $frnd_mod->getFriends($userid,$options); 
	    }
	    else if(!empty($search) && empty($filter)) 
	    {						
			$ttl_list = $frnd_mod->search($userid,$search,'username');
		}
		else
		{
			$ttl_list = $frnd_mod->getFriends($userid,$options);
		}

	    $frnd_list['data'] = $mapp->mapItem( $ttl_list,'user',$userid);
	    $frnd_list['data'] = $mapp->frnd_nodes( $frnd_list['data'],$user);
	    //get other data
	    foreach($frnd_list['data'] as $ky=>$lval)
	    {	
			//get mutual friends of given user
			if( $lval->id != $user->id)
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($user->id,$lval->id);
				
				//if( $user->id != $lval->id )
				$lval->isFriend = $frnd_mod->isFriends( $user->id,$lval->id );
				$lval->isself = false;
				//$lval->mutual_frnds = $frnd_mod->getMutualFriends($userid,$lval->id);
			}
			else
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($userid,$lval->id);
				$lval->isFriend = $frnd_mod->isFriends($userid,$lval->id);
				$lval->isself = true;
			}

			//$lval->mutual = $frnd_mod->getMutualFriendCount($user->id,$lval->id);
			//$lval->isFriend = $frnd_mod->isFriends($user->id,$lval->id);
		}
		//if data is empty givin respective message and status.
		if(empty($frnd_list['data']))
		{				
			$frnd_list['data']['message'] = $mssg;
			$frnd_list['data']['status'] = false;    
		}
		//pending
		 $frnd_list['status']['pendings'] = $frnd_mod->getTotalPendingFriends( $userid );
		 //all frined
		 $frnd_list['status']['allfriend'] = $frnd_mod->getTotalFriends( $userid , array( 'state' => SOCIAL_FRIENDS_STATE_FRIENDS ) );
			//suggested
		 $frnd_list['status']['suggested'] = $frnd_mod->getSuggestedFriends( $userid, null, true );
		 //request sent		 
		 $frnd_list['status']['sentreq']   = $frnd_mod->getTotalRequestSent( $userid );
		 //invited
		 $frnd_list['status']['invite']    = $frnd_mod->getTotalInvites( $userid );
		return( $frnd_list );
	}
}
