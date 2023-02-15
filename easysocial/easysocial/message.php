<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugins
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;


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
		$this->getConversations();
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
		$this->newMessage();
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function newMessage()
	{
		$app			=	Factory::getApplication();
		$recipients		=	$app->input->get('recipients', null, 'ARRAY');
		$msg			=	$app->input->get('message', null, 'RAW');

		// $target_usr	=	$app->input->get('target_user', 0, 'INT');
		$conversion_id	=	$app->input->get('conversion_id', 0, 'INT');
		$log_usr		=	$this->plugin->get('user')->id;
		$canCreate = ES::user();

		// Normalize CRLF (\r\n) to just LF (\n)
		$msg			=	str_ireplace("\r\n", "\n", $msg);

		$res				=	new stdclass;

		// Check if the user really has access to create groups
		if (! $canCreate->getAccess()->allowed('conversations.create') && ! $canCreate->isSiteAdmin())
		{
			ApiError::raiseError(403, Text::_('COM_EASYSOCIAL_CONVERSATIONS_ERROR_NOT_ALLOWED'));
		}

		if (count($recipients) < 1)
		{
			ApiError::raiseError(403, Text::_('PLG_API_EASYSOCIAL_EMPTY_MESSAGE_MESSAGE'));
		}

		// Message should not be empty.
		if (empty($msg))
		{
			ApiError::raiseError(403, Text::_('PLG_API_EASYSOCIAL_EMPTY_MESSAGE_MESSAGE'));
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
			$res->result->status = 1;
			$res->result->message = Text::_('PLG_API_EASYSOCIAL_MESSAGE_SENT_MESSAGE');
		}
		else
		{
			// Create result obj
			ApiError::raiseError(400, Text::_('PLG_API_EASYSOCIAL_UNABLE_SEND_MESSAGE'));
		}

		$this->plugin->setResponse($res);
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
	private function createConversion($recipients, $log_usr, $msg)
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
	 * @return  ApiPlugin response object
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app			=	Factory::getApplication();
		$conversion_id	=	$app->input->get('conversation_id', 0, 'INT');

		$res				=	new stdclass;

		if (!$conversion_id)
		{
			ApiError::raiseError(403, Text::_('PLG_API_EASYSOCIAL_INVALID_CONVERSATION_MESSAGE'));
		}

		// Try to delete the group
		$conv_model = ES::model('Conversations');
		$res->result->status = $conv_model->delete($conversion_id, $this->plugin->get('user')->id);
		$res->result->message = Text::_('PLG_API_EASYSOCIAL_CONVERSATION_DELETED_MESSAGE');

		$this->plugin->setResponse($res);
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return  ApiPlugin response object
	 *
	 * @since 1.0
	 */

	private function getConversations()
	{
		// Init variable
		$app				=	Factory::getApplication();
		$log_user			=	Factory::getUser($this->plugin->get('user')->id);
		$conversation_id	=	$app->input->get('conversation_id', 0, 'INT');
		$limitstart			=	$app->input->get('limitstart', 0, 'INT');
		$limit				=	$app->input->get('limit', 500, 'INT');
		$maxlimit			=	$app->input->get('maxlimit', 100, 'INT');
		$filter				=	$app->input->get('filter', null, 'STRING');

		$mapp				=	new EasySocialApiMappingHelper;
		$user				=	ES::user($log_user->id);

		$res				=	new stdclass;
		$res->result		=	array();
		$res->empty_message	=	'';

		$conv_model			=	ES::model('Conversations');

		// Set the startlimit
		$conv_model->setState('limitstart', $limitstart);

		if ($conversation_id)
		{
			$data['participant']	=	$this->getParticipantUsers($conversation_id);
			$msg_data				=	$conv_model->setLimit($limit)->getMessages($conversation_id, $log_user->id);
			$res->result			=	$mapp->mapItem($msg_data, 'message', $log_user->id);
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
				$res->result = $mapp->mapItem($conversion, 'conversion', $log_user->id);
				$res->result = array_slice($res->result, $limitstart, $limit);
			}
			else
			{
				$res->empty_message = Text::_('COM_EASYSOCIAL_CONVERSATION_EMPTY_LIST');
			}
		}

		$this->plugin->setResponse($res);
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
	private function getParticipantUsers($con_id)
	{
		$conv_model			=	ES::model('Conversations');
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
	 *
	private function uploadFile()
	{
		$config		=	ES::config();
		$limit		=	$config->get($type . '.attachments.maxsize');

		// Set uploader options
		$options	=	array(
							'name' => 'file',
							'maxsize' => $limit . 'M'
						);

		// Let's get the temporary uploader table.
		$uploader 			= ES::table('Uploader');
		$uploader->user_id	= $this->plugin->get('user')->id;

		// Pass uploaded data to the uploader.
		$uploader->bindFile($data);
		$state 	= $uploader->store();
	}
	*/
}
