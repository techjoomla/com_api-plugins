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
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/friends.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/users.php';

/**
 * API class EasysocialApiResourceManage_friends
 *
 * @since  1.0
 */
class EasysocialApiResourceManage_Friends extends ApiResource
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
		$this->plugin->setResponse($this->manageFriends());
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
		$this->plugin->setResponse($this->manageFriends());
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function manageFriends()
	{
		// Init variable
		$app	=	JFactory::getApplication();

		$log_user	=	JFactory::getUser($this->plugin->get('user')->id);
		$db			=	JFactory::getDbo();
		$frnd_id	=	$app->input->post->get('friend_id', 0, 'INT');
		$choice		=	$app->input->post->get('choice', 0, 'INT');
		$userid		=	$log_user->id;
		$res		=	new stdClass;

		if (!$frnd_id)
		{
			return JText::_('PLG_API_EASYSOCIAL_FRIEND_ID_NOT_FOUND');
		}

		if ($choice)
		{
			$frnds_obj	=	new EasySocialModelFriends;
			$result		=	$frnds_obj->request($frnd_id, $userid);

			if ($result->id)
			{
				$res->frnd_id	=	$frnd_id;
				$res->code		=	200;
				$res->message	=	JText::_('COM_EASYSOCIAL_FRIENDS_REQUEST_SENT');
			}
			else
			{
				$res->code		=	403;
				$res->message	=	$result;
			}
		}
		else
		{
			$user			=	FD::user($id);
			$user->approve();
			$res->result	=	EasySocialModelUsers::deleteFriends($frnd_id);

			if ($res->result == true)
			{
				$res->code		=	200;
				$res->message	=	JText::_('PLG_API_EASYSOCIAL_FRIEND_DELETED_MESSAGE');
			}
			else
			{
				$res->code		=	403;
				$res->message	=	JText::_('PLG_API_EASYSOCIAL_UNABLE_DELETE_FRIEND_MESSAGE');
			}
		}

		return $res;
	}
}
