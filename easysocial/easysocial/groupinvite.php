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
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

/**
 * API class EasysocialApiResourceGroupinvite
 *
 * @since  1.0
 */
class EasysocialApiResourceGroupinvite extends ApiResource
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
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_EASYSOCIAL_USE_POST_OR_DELETE_MESSAGE');
		$this->plugin->setResponse(null);
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
		$this->inviteGroup();
	}

	/**
	 * Method description Method used to leave group/page or delete group/page.
	 *
	 * @param   array  $cluster  cluster name
	 * 
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function leaveGroupPage($cluster)
	{
		$app			=	JFactory::getApplication();
		$target_user	=	$app->input->get('target_user', 0, 'INT');
		$operation		=	$app->input->get('operation', 0, 'STRING');
		$valid			=	1;
		$res			=	new stdClass;

		if (!$target_user)
		{
			$res->result->status		=	0;
			$res->result->message		=	JText::_('PLG_API_EASYSOCIAL_INVALID_USER_MESSAGE');
			$valid						=	0;
		}

		// Only allow super admins to delete groups
		$my		=	FD::user($this->plugin->get('user')->id);

		if ($target_user == $my->id && $operation == 'leave' && $cluster->creator_uid == $my->id)
		{
			$res->result->status	=	0;
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_GROUP_OWNER_NOT_LEAVE_MESSAGE');
			$valid					=	0;
		}

		// Target user obj
		$user 					=	FD::user($target_user);

		if ($valid)
		{
			switch ($operation)
			{
				case 'leave':
								// Remove the user from the group/page.
								$res->result->status = $cluster->leave($user->id);

								if (!$res->result->status)
								{
									$res->result->status	=	0;
									$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_USER_MESSAGE');
								}
								else
								{
									// Notify group/page members
									$cluster->notifyMembers('leave', array('userId' => $my->id));
									$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_LEAVE_GROUP_MESSAGE');
								}
								break;

				case 'remove':

								// Remove the user from the group/page.
								$res->result->status = $cluster->deleteMember($user->id);

								if (!$res->result->status)
								{
									$res->result->status	=	0;
									$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_USER_MESSAGE');
								}
								else
								{
									// Notify group/page member
									$cluster->notifyMembers('user.remove', array('userId' => $user->id));
									$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_USER_REMOVE_SUCCESS_MESSAGE');
								}
								break;
			}

			$res->result->status	=	1;
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method description Method used to leave group or delete group.
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app			=	JFactory::getApplication();
		$group_id		=	$app->input->get('group_id', 0, 'INT');
		$page_id		=	$app->input->get('page_id', 0, 'INT');
		$valid			=	1;
		$res			=	new stdClass;

		if (!$group_id && !$page_id)
		{
			$res->result->status 	=	0;
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_ID');
			$valid					=	0;
		}

		if ($group_id)
		{
			$group			=	FD::group($group_id);

			if (!$group->id)
			{
				$res->result->status 	=	0;
				$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_GROUP_MESSAGE');
				$valid					=	0;
			}

			$this->leaveGroupPage($group_id, $group, 'group');
		}
		else
		{
			$page			=	FD::page($page_id);

			if (!$page->id)
			{
				$res->result->status 	=	0;
				$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_PAGE_MESSAGE');
				$valid					=	0;
			}

			$this->leaveGroupPage($page_id, $page, 'page');
		}
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function inviteGroup()
	{
		// Init variable
		$app			=	JFactory::getApplication();
		$log_user		=	JFactory::getUser($this->plugin->get('user')->id);
		$res			=	new stdClass;
		$group_id		=	$app->input->get('group_id', 0, 'INT');
		$target_users	=	$app->input->get('target_users', null, 'ARRAY');
		$user			=	FD::user($log_user->id);
		$grp_model		=	FD::model('Groups');
		$group			=	FD::group($group_id);

		if ($group_id)
		{
			$not_invi	=	array();
			$invited	=	array();
			$es_params	=	FD::config();

			foreach ($target_users as $id)
			{
				$target_username	=	JFactory::getUser($id)->name;

				if ($es_params->get('users')->displayName == 'username')
				{
					$target_username = JFactory::getUser($id)->username;
				}

				// Check that the user is not a member or has been invited already
				if (!$group->isMember($id) && !$grp_model->isInvited($id, $group_id))
				{
					$state		=	$group->invite($id, $log_user->id);
					$invited[]	=	$target_username;
				}
				else
				{
					$not_invi[]	=	$target_username;
				}
			}

			$res->result->status		=	1;
			$res->result->invited		=	$invited;
			$res->result->not_invtited	=	$not_invi;
		}

		$this->plugin->setResponse($res);
	}
}
