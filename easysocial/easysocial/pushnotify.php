<?php
/**
* @package		%PACKAGE%
* @subpackge	%SUBPACKAGE%
* @copyright	Copyright (C) 2010 - 2012 %COMPANY_NAME%. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
*
* %PACKAGE% is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined( '_JEXEC' ) or die( 'Unauthorized Access' );

// We want to import our app library
Foundry::import( 'admin:/includes/apps/apps' );

/**
 * Some application for EasySocial. Take note that all classes must be derived from the `SocialAppItem` class
 *
 * Remember to rename the Textbook to your own element.
 * @since	1.0
 * @author	Author Name <author@email.com>
 */
class SocialUserAppPushnotify extends SocialAppItem
{
	/**
	 * Class constructor.
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Triggers the preparation of stream.
	 *
	 * If you need to manipulate the stream object, you may do so in this trigger.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	SocialStreamItem	The stream object.
	 * @param	bool				Determines if we should respect the privacy
	 */
	public function onPrepareStream( SocialStreamItem &$item, $includePrivacy = true )
	{
		// You should be testing for app context
		if( $item->context !== 'appcontext' )
		{
			return;
		}
	}

	/**
	 * Triggers the preparation of activity logs which appears in the user's activity log.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	SocialStreamItem	The stream object.
	 * @param	bool				Determines if we should respect the privacy
	 */
	public function onPrepareActivityLog( SocialStreamItem &$item, $includePrivacy = true )
	{
	}

	/**
	 * Triggers after a like is saved.
	 *
	 * This trigger is useful when you want to manipulate the likes process.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	SocialTableLikes	The likes object.
	 *
	 * @return	none
	 */
	public function onAfterLikeSave( &$likes )
	{
	}

	/**
	 * Triggered when a comment save occurs.
	 *
	 * This trigger is useful when you want to manipulate comments.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	SocialTableComments	The comment object
	 * @return
	 */
	public function onAfterCommentSave( &$comment )
	{
	}

	/**
	 * Renders the notification item that is loaded for the user.
	 *
	 * This trigger is useful when you want to manipulate the notification item that appears
	 * in the notification drop down.
	 *
	 * @since	1.0
	 * @access	public
	 * @param	string
	 * @return
	 */
	public function onNotificationBeforeCreate( $item_dt )
	{
		//checking table is exists in database then only execute patch.
		$db = FD::db();		
		$stbl = $db->getTableColumns('#__social_gcm_users');
		$reg_ids=array();
		$targetUsers=array();
		// Create a new query object.
		$query = $db->getQuery(true);
		// Select records from the user social_gcm_users table".
		$query->select($db->quoteName(array('device_id', 'sender_id', 'server_key', 'user_id')));
		$query->from($db->quoteName('#__social_gcm_users'));
		// Reset the query using our newly populated query object.
		$db->setQuery($query);
		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$urows = $db->loadObjectList();

		$rule = $item_dt->rule;

		//Generate element from rule.
		$segments = explode('.', $rule);
		$element = array_shift($segments);

		$participants = $item_dt->participant;
		$emailOptions = $item_dt->email_options;
		$systemOptions = $item_dt->sys_options;
		
		$msg_data = $this->createMessageData( $element,$emailOptions, $systemOptions, $participants );
	
		$targetUsers=$msg_data['tusers'];
		
		$count = rand(1,100);
		$user = FD::user();
		$actor_name=$user->username;
		
		$tit=JText::_($msg_data['title']);
	 
		$tit=str_replace('{actor}',$actor_name,$tit);
		
		foreach($urows as $notfn)
		{
			if(in_array($notfn->user_id,$targetUsers))
			{
				   $reg_ids[]=$notfn->device_id;                                
				   $server_k=$notfn->server_key;                                
			}					
		}		
		//build message for GCM
		if(!empty($reg_ids))
		{		
			//increment counter
			$registatoin_ids = $reg_ids;
			// Message to be sent
			$message = $tit;
			//Google cloud messaging GCM-API url
			$url = 'https://gcm-http.googleapis.com/gcm/send';
			
			//Setting headers for gcm service.
			$headers = array(
			'Authorization'=>'key='.$server_k,
			'Content-Type'=> 'application/json'
			);
			//Setting fields for gcm service.
			//fields contents what data to be sent.
			$fields = array(
			'registration_ids' => $registatoin_ids,
			'data' => array( "title" => $message,"message" => $msg_data['mssge'] ,"notId"=>$count,"url" => $msg_data['ul'], "body"=>$msg_data['mssge']),			
			); 
	
			//Making call to GCM  API using POST.
			jimport('joomla.client.http');
			//Using JHttp for API call
			$http      = new JHttp;
			$options   = new JRegistry;
			//$transport = new JHttpTransportStream($options);
			
			$http = new JHttp($options);
			$gcmres = $http->post($url,json_encode($fields),$headers);			
		}
	}
 
 public function  createMessageData($element,$emailOptions, $systemOptions, $participants)
 {	
		$data = array();
		//switch case for getting url,avatar of actor,data for particular view
		
		$emailOptions = (is_object($emailOptions))?(array)$emailOptions:$emailOptions;
		$systemOptions = (is_object($systemOptions))?(array)$systemOptions:$systemOptions;
		
		$data['title'] = $emailOptions['title'];
		$data['title'] = JText::_($data['title']);
		
		$data['tusers'] = $this->createParticipents($participants);
		switch($element){
			case 'conversations':	$data['ul'] = $emailOptions['conversationLink'];
									$data['mssge'] = $emailOptions['message'];
									$data['authorAvatar'] = $emailOptions['authorAvatar'];	
									$data['actor'] = $emailOptions['authorName'];	
								
			break;
			case 'friends':			$data['ul'] = $emailOptions['params']['requesterLink'];
									$data['authorAvatar'] = $emailOptions['params']['requesterAvatar'];
									$data['authorlink'] = $emailOptions['params']['requesterLink'];
									$data['mssge']= ' ';
									$data['actor']=$emailOptions['actor'];
			break;
			case 'profile':
									
									if($rulename=='followed')
									{
										$data['ul']=$emailOptions['targetLink'];
										$data['mssge']=' ';	
										$data['authorAvatar']=$emailOptions['actorAvatar'];
									}
									else
									{
										$data['ul'] = $emailOptions['params']['permalink'];
										$data['authorAvatar'] = $emailOptions['params']['actorAvatar'];
										$data['mssge'] = $emailOptions['params']['content'];
										$data['actor'] = $emailOptions['params']['actor'];
										$data['target_user'] = $systemOptions['target_id'];
									}
			break;						
			case 'likes':			
									$data['authorAvatar']=$emailOptions['actorAvatar'];
									$data['mssge']=' ';
									$data['ul']=$emailOptions['permalink'];
									$data['target_link']=$emailOptions['targetLink'];
									$data['target_name']=$systemOptions['target'];
			break;
			case 'comments':
									$data['authorAvatar']=$emailOptions['actorAvatar'];
									$data['mssge']=$emailOptions['comment'];
									$data['ul']=$emailOptions['permalink'];
									$data['target_link']=$emailOptions['targetLink'];
									$data['target_name']=$systemOptions['target'];
			break;
			case 'events':
//print_r($emailOptions);die("in notify");	
	                            $data['authorAvatar']= (isset($emailOptions['posterAvatar']))?$emailOptions['posterAvatar']:$emailOptions['actorAvatar'];
                                    $data['mssge']=$emailOptions['message'];
                                    $data['ul']=$emailOptions['eventLink'];
                                    $data['actor']=$emailOptions['actor'];
                                    //this line is for getting event name in notification.
                                    $data['title']=str_replace('{event}',$emailOptions['event'],$data['title']);
                                    
            break;
            case 'groups':

                                    $data['authorAvatar']=(isset($emailOptions['posterAvatar']))?$emailOptions['posterAvatar']:$emailOptions['params']->userAvatar;
                                    $data['mssge']=(isset($emailOptions['message']))?$emailOptions['message']:null;
                                    $data['ul']= (isset($emailOptions['groupLink']))?$emailOptions['groupLink']:$emailOptions['params']->groupLink;
                                    //this line is for getting group name in notification.
				    $group_ttl = (isset($emailOptions['group']))?$emailOptions['group']:$emailOptions['params']->group;	
                                    $data['title']=str_replace('{group}',$group_ttl,$data['title']);
            break;
            case 'stream':
									$data['authorAvatar']=$emailOptions['actorAvatar'];
                                    $data['mssge']=$emailOptions['message'];
                                    $data['ul']=$emailOptions['permalink'];                                 
            break;
		}
		return $data;
 }
 
 //create participents unique abjects
 public function createParticipents($pUsers)
 {	
	$userObj = is_object($pUsers[count($pUsers)-1]);
	if($userObj)
	{
		$myarr = array();
		foreach($pUsers as $ky=>$row)
		{
			$myarr[]= $row->id;
		}		
		return $myarr;
	}
	else
	{		
		return $pUsers;
	}
 }
 
/*
 public function onNotificationBeforeAlert($commonObj)
 {
		$ex_arr = array('events','groups');
		
		$rule = $commonObj->rule;
		//Generate element from rule.
		$segments = explode('.', $rule);
		$element = array_shift($segments);
		
		if(!in_array($element,$ex_arr))
        {
		
		$participants = $commonObj->participant;
		$emailOptions = $commonObj->email_options;
		$systemOptions = $commonObj->sys_options;
		//checking table is exists in database then only execute patch.
		$db = FD::db();

		$stbl = $db->getTableColumns('#__social_gcm_users');
		if(count($stbl))
		{		
		if(is_object($emailOptions))
		{
			$emailOptions = (array)$emailOptions;
		}
		$count = rand(1,100);
		//Get actor.		
		$user = FD::user();
		$actor_name=$user->username;	
		//Convert array into separate variable.	
		$tit=JText::_($emailOptions['title']);		
		//Tried using sprintf.
		$messg=str_replace('{actor}',$actor_name,$tit);
		$reg_ids=array();
		// Get a db connection.
		//$db = FD::db();
		// Create a new query object.
		$query = $db->getQuery(true);
		// Select records from the user social_gcm_users table".
		$query->select($db->quoteName(array('device_id', 'sender_id', 'server_key', 'user_id')));
		$query->from($db->quoteName('#__social_gcm_users'));
		// Reset the query using our newly populated query object.
		$db->setQuery($query);
		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$results = $db->loadObjectList();
		foreach($results as $notfn)
		{
			if(in_array($notfn->user_id,$participants))
			{
				   $reg_ids[]=$notfn->device_id;                                
				   $server_k=$notfn->server_key;                                
			}
		//This else is only for conversations reply
			else if(($rule == 'conversations.reply') && ($notfn->user_id == $participants[0]->id))
			{			
				   $reg_ids[]=$notfn->device_id;                                
				   $server_k=$notfn->server_key;
			}
		}
		//switch case for getting url,avatar of actor,data for particular view
		switch($element){
			case 'conversations':	$ul = $emailOptions['conversationLink'];
									$mssge = $emailOptions['message'];
									$authorAvatar = $emailOptions['authorAvatar'];	
			break;
			case 'friends':			$ul = $emailOptions['params']['requesterLink'];
									$authorAvatar = $emailOptions['params']['requesterAvatar'];
									$mssge=' ';
			break;
			case 'profile';			
									if($rulename=='followed')
									{
										$ul=$emailOptions['targetLink'];
										$mssge='';	
										$authorAvatar=$emailOptions['actorAvatar'];
									}
									else
									{
										$ul = $emailOptions['params']['permalink'];
										$authorAvatar = $emailOptions['params']['actorAvatar'];
										$mssge = $emailOptions['params']['content'];
									}
			break;						
			case 'likes':
			case 'comments':
									$authorAvatar=$emailOptions['actorAvatar'];
									$mssge=' ';
									$ul=$emailOptions['permalink'];
			break;
			case 'events':
                                    $authorAvatar=$emailOptions['actorAvatar'];
                                    $mssge=$emailOptions['response'];
                                    $ul=$emailOptions['eventLink'];
                                    //this line is for getting event name in notification.
                                    $messg=str_replace('{event}',$emailOptions['event'],$messg);
                                    
            break;
            case 'groups':
                                    $authorAvatar=$emailOptions['params']->userLink;
                                    $mssge=' ';
                                    $ul=$emailOptions['params']->groupLink;
            break;
		}
				
		//If reg ids are not empty then call gcm service.		
		if(!empty($reg_ids))
		{
			//increment counter
			$registatoin_ids = $reg_ids;
			// Message to be sent
			$message = $messg;	
			//Google cloud messaging GCM-API url
			$url = 'https://gcm-http.googleapis.com/gcm/send';
			
			//Setting headers for gcm service.
			$headers = array(
			'Authorization'=>'key='.$server_k,
			'Content-Type'=> 'application/json'
			);
			//Setting fields for gcm service.
			//fields contents what data to be sent.
			$fields = array(
			'registration_ids' => $registatoin_ids,
			'data' => array( "message" => $message ,"notId"=>$count,"url" => $ul, "body"=>$mssge),			
			);  
			//Making call to GCM  API using POST.
			jimport('joomla.client.http');
			//Using JHttp for API call
			$http      = new JHttp;
			$options   = new JRegistry;
			//$transport = new JHttpTransportStream($options);
			
			$http = new JHttp($options);	
		
			$gcmres = $http->post($url,json_encode($fields),$headers);			
		}
		}
	}
 }
 */
	
}

