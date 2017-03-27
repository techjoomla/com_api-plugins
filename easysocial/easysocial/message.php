<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceMessage
 *
 * @since  1.0
 */

class EasysocialApiResourceMessage extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getConversations());
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->plugin->setResponse($this->newMessage());
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function newMessage()
	{
		$app			=	JFactory::getApplication();
		$recipients		=	$app->input->get('recipients', null, 'ARRAY');
		$msg			=	$app->input->get('message', null, 'RAW');

		// $target_usr	=	$app->input->get('target_user', 0, 'INT');
		$conversion_id	=	$app->input->get('conversion_id', 0, 'INT');
		$log_usr		=	$this->plugin->get('user')->id;

		// Normalize CRLF (\r\n) to just LF (\n)
		$msg			=	str_ireplace("\r\n", "\n", $msg);

		$result			=	new stdClass;

		if (count($recipients) < 1)
		{
			$result->id			=	0;
			$result->status		=	0;
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_EMPTY_MESSAGE_MESSAGE');

			return $result;
		}

		// Message should not be empty.
		if (empty($msg))
		{
			$result->id			=	0;
			$result->status		=	0;
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_EMPTY_MESSAGE_MESSAGE');

			return $result;
		}

		if ($conversion_id == 0)
		{
			$state	=	$this->createConversion($recipients, $log_usr, $msg);
		}

		if ($conversion_id)
		{
			$conversation			=	ES::conversation($conversion_id);
			$post_data['uid']		=	$recipients;
			$post_data['message']	=	$msg;
			$conversation->bind($post_data);
			$state					=	$conversation->save();
		}

		if ($state)
		{
			$result->status		=	1;
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_MESSAGE_SENT_MESSAGE');
		}
		else
		{
			// Create result obj
			$result->status		=	0;
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_UNABLE_SEND_MESSAGE');
		}

		return $result;
	}

	/**
	 * Method createConversion
	 *
	 * @param   array   $recipients  array of receipients
	 * @param   int     $log_usr     logged in user id
	 * @param   string  $msg         message
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function createConversion($recipients, $log_usr, $msg)
	{
		$conversation	=	ES::conversation();
		$allowed		=	$conversation->canCreate();

		if (!$allowed)
		{
			return false;
		}

		$post_data['uid']		=	$recipients;
		$post_data['message']	=	$msg;
		$conversation->bind($post_data);
		$state					=	$conversation->save();

		return $state;
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app			=	JFactory::getApplication();
		$conversion_id	=	$app->input->get('conversation_id', 0, 'INT');
		$valid			=	1;
		$result			=	new stdClass;

		if (!$conversion_id)
		{
			$result->status		=	0;
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_CONVERSATION_MESSAGE');
			$valid				=	0;
		}
		else
		{
			// Try to delete the group
			$conv_model			=	FD::model('Conversations');
			$result->status		=	$conv_model->delete($conversion_id, $this->plugin->get('user')->id);
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_CONVERSATION_DELETED_MESSAGE');
		}

		$this->plugin->setResponse($result);
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function getConversations()
	{
		// Init variable
		$app				=	JFactory::getApplication();
		$log_user			=	JFactory::getUser($this->plugin->get('user')->id);
		$conversation_id	=	$app->input->get('conversation_id', 0, 'INT');
		$limitstart			=	$app->input->get('limitstart', 0, 'INT');
		$limit				=	$app->input->get('limit', 20, 'INT');
		$maxlimit			=	$app->input->get('maxlimit', 100, 'INT');
		$filter				=	$app->input->get('filter', null, 'STRING');
		$mapp				=	new EasySocialApiMappingHelper;
		$data				=	array();
		$data['data']		=	array();
		$user				=	FD::user($log_user->id);
		$mapp				=	new EasySocialApiMappingHelper;
		$conv_model			=	FD::model('Conversations');
		$conv_model->setState('limitstart', 0);

		// Set the startlimit
		$conv_model->setState('limitstart', $limitstart);

		if ($conversation_id)
		{
			$data['participant']	=	$this->getParticipantUsers($conversation_id);
			$msg_data				=	$conv_model->setLimit($limit)->getMessages($conversation_id, $log_user->id);
			$data['data']			=	$mapp->mapItem($msg_data, 'message', $log_user->id);

			return $data;
		}
		else
		{
			// Sort items by latest first
			$options 		=	array('sorting' => 'lastreplied', 'maxlimit' => $maxlimit);

			if ($filter)
			{
				$options['filter']	=	$filter;
			}

			$conversion		=	$conv_model->getConversations($log_user->id, $options);

			/*$conversation->conversation->isparticipant = $row->isparticipant;
			 *$conversation = ES::conversation($row->id);
			 *$msg = $conversation->getMessages();
			*/
			if (count($conversion) > 0)
			{
				$data['data']		=	$mapp->mapItem($conversion, 'conversion', $log_user->id);
				$data['data']		=	array_slice($data['data'], $limitstart, $limit);
				$data['status']		=	true;
			}
			else
			{
				$data['message']	=	JText::_('COM_EASYSOCIAL_CONVERSATION_EMPTY_LIST');
				$data['status']		=	false;
			}

			return $data;
		}
	}

	/**
	 * Method getParticipantUsers
	 *
	 * @param   int  $con_id  Conversation ID
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getParticipantUsers($con_id)
	{
		$conv_model			=	FD::model('Conversations');
		$mapp				=	new EasySocialApiMappingHelper;
		$participant_usrs	=	$conv_model->getParticipants($con_id);
		$con_usrs			=	array();

		foreach ($participant_usrs as $ky => $usrs)
		{
			if ($usrs->id && ($this->plugin->get('user')->id != $usrs->id))
			{
				$con_usrs[] = $mapp->createUserObj($usrs->id);
			}

			return $con_usrs;
		}
	}

	/**
	 * Method function for upload file
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function uploadFile()
	{
		$config		=	FD::config();
		$limit		=	$config->get($type . '.attachments.maxsize');

		// Set uploader options
		$options	=	array(
							'name' => 'file',
							'maxsize' => $limit . 'M'
						);

		// Let's get the temporary uploader table.
		$uploader 			= FD::table('Uploader');
		$uploader->user_id	= $this->plugin->get('user')->id;

		// Pass uploaded data to the uploader.
		$uploader->bindFile($data);
		$state 	= $uploader->store();
	}
}
