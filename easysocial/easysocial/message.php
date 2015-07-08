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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceMessage extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getConversations());
	}

	public function post()
	{
		$app = JFactory::getApplication();
		$recipients = $app->input->get('recipients',null,'ARRAY');
		$msg = $app->input->get('message',null,'STRING');
		//$target_usr = $app->input->get('target_user',0,'INT');
		$conversion_id = $app->input->get('conversion_id',0,'INT');
		$log_usr = $this->plugin->get('user')->id;
		$valid = 1;
		// Normalize CRLF (\r\n) to just LF (\n)
		$msg = str_ireplace("\r\n", "\n", $msg);
	
	//print_r($recipients);die("in post message");
	
		$result = new stdClass;
		if(count($recipients)<1)
		{
			$result->id = 0;
			$result->status  = 0;
			$result->message = 'Empty message not allowed';
			$valid = 0;
		}
		
		// Message should not be empty.
		if (empty($msg))
		{
			$result->id = 0;
			$result->status  = 0;
			$result->message = 'Empty message not allowed';
			$valid = 0;
		}
		else if($valid)
		{
			$conversion_id = ($conversion_id)?$conversion_id:$this->createConversion($recipients,$log_usr);

			if($conversion_id)
			{
				
				$message = FD::table('ConversationMessage');
				// Bind the message data.
				$post_data = array();
				$post_data['uid'] = $recipients;
				$post_data['message'] = $msg;
				$post_data['upload-id'] = array();
				
				$message->bind($post_data);

				$message->message = $msg;
				$message->conversation_id = $conversion_id;
				$message->type = SOCIAL_CONVERSATION_TYPE_MESSAGE;
				$message->created = FD::date()->toMySQL();
				$message->created_by = $log_usr;

				// Try to store the message now.
				$state = $message->store();

				if($state)
				{
					// Add users to the message maps.
					array_unshift($recipients, $log_usr);

					$model = FD::model('Conversations');

					// Add the recipient as a participant of this conversation.
					$model->addParticipants($conversion_id, $recipients);

					// Add the message maps so that the recipient can view the message
					$model->addMessageMaps($conversion_id, $message->id, $recipients, $log_usr);
				    
				    //create result obj    
					$result->status  = 1;
					$result->message = 'Message send successfully';    
				}
				else
				{
					//create result obj    
					$result->status  = 0;
					$result->message = 'Unable to send message'; 
				}
			}
		}
		
	   $this->plugin->setResponse($result);
	}
	
	//function for upload file
	public function uploadFile()
	{
		$config = FD::config();
		$limit 	= $config->get( $type . '.attachments.maxsize' );

		// Set uploader options
		$options = array(
			'name'        => 'file',
			'maxsize' => $limit . 'M'
		);
		// Let's get the temporary uploader table.
		$uploader 			= FD::table( 'Uploader' );
		$uploader->user_id	= $this->plugin->get('user')->id;

		// Pass uploaded data to the uploader.
		$uploader->bindFile( $data );

		$state 	= $uploader->store();
	}
	
	//create new conversion
	public function createConversion($recipients,$log_usr)
	{
			// Get the conversation table.
			$conversation = FD::table('Conversation');
			
			$type = (count($recipients)>1)?2:1;
			if($type==1)
			{
				$state 	= $conversation->loadByRelation($log_usr, $recipients[0], 1);
			}
			else
			{
				$points = FD::points();
				$points->assign('conversation.create.group', 'com_easysocial', $log_usr);
			}
			// Set the conversation creator.
			$conversation->created_by = $log_usr;
			$conversation->lastreplied = FD::date()->toMySQL();
			$conversation->type = $type;
			
			$state = $conversation->store();
			
			return $conversation->id;

	}
	
	
	public function delete()
	{
		$app = JFactory::getApplication();
		
		$conversion_id = $app->input->get('conversation_id',0,'INT');
		$valid = 1;
		$result = new stdClass;
	
		if( !$conversion_id )
		{
			
			$result->status = 0;
			$result->message = 'Invalid Conversations';
			$valid = 0;
		}
		
		if($valid)
		{
			// Try to delete the group
			$conv_model = FD::model('Conversations');
			//$my 	= FD::user($this->plugin->get('user')->id);
			$result->status = $conv_model->delete( $conversion_id , $this->plugin->get('user')->id );
			$result->message = 'Conversations deleted successfully';
		}
		
		$this->plugin->setResponse($result);
	}
	//function use for get friends data
	function getConversations()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		
		$conversation_id = $app->input->get('conversation_id',0,'INT');
		$limitstart = $app->input->get('limitstart',0,'INT');
		$limit = $app->input->get('limit',10,'INT'); 
		$maxlimit = $app->input->get('maxlimit',100,'INT'); 
		$filter = $app->input->get('filter',null,'STRING');
		
		$mapp = new EasySocialApiMappingHelper();
		
		$data = array();
		
		$user = FD::user($log_user->id);
		
		$mapp = new EasySocialApiMappingHelper();
		
		$conv_model = FD::model('Conversations');
		
		if($conversation_id)
		{
			$data['participant'] = $this->getParticipantUsers( $conversation_id );
			$msg_data = $conv_model->getMessages($conversation_id,$log_user->id);
		
			$data['data'] = $mapp->mapItem($msg_data,'message',$log_user->id);
			return $data;
		}
		else
		{
			//sort items by latest first
			$options 		= array( 'sorting' => 'lastreplied', 'maxlimit' => $maxlimit );
			
			if( $filter )
			{
				$options['filter'] = $filter;
			}
			
			$conversion = $conv_model->getConversations( $log_user->id , $options );
	
			if(count($conversion)>0)
			{
				/*foreach($conversion as $key=>$node)
				{

					$cobj = new stdClass;
					$cobj->conversion_id = $node->id;
					$cobj->created_date = $node->created;
					$cobj->lastreplied_date = $node->lastreplied;
					$cobj->isread = $node->isread;
					$cobj->messages = $node->message;
					$cobj->participant = $this->getParticipantUsers( $node->id );
					
					$raw_msg = $conv_model->getMessages($node->id , $log_user->id );
					
					$cobj->messages = $mapp->mapItem($raw_msg,'message',$log_user->id);
					
					$data['data'][] = $cobj;
				}*/
				$data['data'] = $mapp->mapItem($conversion,'conversion',$log_user->id);
			}
			
			//manual pagination code
			$data['data'] = array_slice( $data['data'], $limitstart, $limit );
			
			return( $data );
		}
	}
	
	//get conversations particepents
	public function getParticipantUsers($con_id)
	{
		$conv_model = FD::model('Conversations');
		$mapp = new EasySocialApiMappingHelper();
		
		$participant_usrs = $conv_model->getParticipants( $con_id );
		
		$con_usrs = array();

		foreach($participant_usrs as $ky=>$usrs)
		{
			if($usrs->id && ($this->plugin->get('user')->id != $usrs->id) )
			$con_usrs[] =  $mapp->createUserObj($usrs->id);
		}
		return $con_usrs;
	}
	
	
}
