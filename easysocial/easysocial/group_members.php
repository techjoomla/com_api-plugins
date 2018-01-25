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

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groupmembers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/model.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceGroup_members
 *
 * @since  1.0
 */
class EasysocialApiResourceGroup_Members extends ApiResource
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
		$this->getGroup_Members();
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
		$this->joineGroup();
	}

	/**
	 * Method description
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function getGroup_Members()
	{
		// Init variable
		$app		=	JFactory::getApplication();
		$log_user	=	$this->plugin->get('user')->id;
		$clusterId	=	$app->input->get('id', 0, 'INT');
		$limitstart	=	$app->input->get('limitstart', 0, 'INT');
		$limit		=	$app->input->get('limit', 10, 'INT');
		$mapp		=	new EasySocialApiMappingHelper;
		$data		=	array();
		$filter		=	$app->input->get('filter', 'admins', 'STRING');

		if ($limitstart)
		{
			$limit += $limitstart;
		}

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		// For filter user by type
		$type		=	$app->input->get('type', 'group', 'STRING');
		$state		=	$app->input->get('state', 1, 'INT');
		$getAdmin	=	$app->input->get('admin', 1, 'INT');

		if ($type === 'group' || $type === 'page')
		{
			$data	=	$this->fetchGroupMembers($clusterId, $limit, $log_user, $mapp);
		}
		elseif ($type === 'event')
		{
			$data	=	$this->getEventMembers($clusterId, $filter, $log_user, $mapp);
		}

		if (empty($data))
		{
			$res->empty_message	=	JText::_('PLG_API_EASYSOCIAL_MEMBER_NOT_FOUND_MESSAGE');
		}

		// Manual pagination code
		$res->result	=	array_slice($data, $limitstart, $limit);

		$this->plugin->setResponse($res);
	}

	/**
	 * Method this common function is for getting dates for month,year,today,tomorrow filters.
	 *
	 * @param   int     $clusterId  cluster id
	 * @param   string  $filter     filter
	 * @param   string  $log_user   logged user id
	 * @param   string  $mapp       mapp object
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getEventMembers($clusterId, $filter, $log_user, $mapp)
	{
		// Get event guest with filter.
		$grp_model = FD::model('Events');

		if (!empty($filter))
		{
			$options['users']	=	true;

			switch ($filter)
			{
				case 'going':
								$options['state'] = SOCIAL_EVENT_GUEST_GOING;
				break;
				case 'notgoing':
								$options['state'] = SOCIAL_EVENT_GUEST_NOT_GOING;
				break;
				case 'maybe':
								$options['state'] = SOCIAL_EVENT_GUEST_MAYBE;
				break;
				case 'pending':
								$options['state'] = SOCIAL_EVENT_GUEST_PENDING;
								$options['users'] = true;
				break;
				case 'admins':
								$options['admin'] = true;
				break;
			}

			$eguest	=	FD::model('Events');
			$data	=	$eguest->getGuests($clusterId, $options);
			$data	=	$mapp->mapItem($data, 'user', $log_user);

			if ($filter == 'pending')
			{
				$options['state']	=	SOCIAL_EVENT_GUEST_PENDING;
				$options['users']	=	false;
				$udata			=	$eguest->getGuests($clusterId, $options);

				foreach ($udata as $usr)
				{
					foreach ($data as $dt)
					{
						if ($usr->uid == $dt->id)
						{
							$dt->request_id			=	$usr->id;
							$dt->request_state		=	$usr->state;
							$dt->isowner			=	$usr->isOwner();
							$dt->isStrictlyAdmin	=	$usr->isStrictlyAdmin();
							$dt->isGoing			=	$usr->isGoing();
							$dt->isMaybe			=	$usr->isMaybe();
							$dt->isNotGoing			=	$usr->isNotGoing();
							$dt->isPending			=	$usr->isPending();
						}
					}
				}
			}

			return $data;
		}
	}

	/**
	 * Method this common function is for getting dates for month,year,today,tomorrow filters.
	 *
	 * @param   int     $clusterId  cluster id
	 * @param   string  $limit      limit
	 * @param   string  $log_user   logged user id
	 * @param   string  $mapp       mapp object
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function fetchGroupMembers($clusterId, $limit, $log_user, $mapp)
	{
		$grp_model		=	FD::model('Groups');
		$options		=	array('groupid' => $clusterId);
		$gruserob		=	new EasySocialModelGroupMembers;
		$gruserob->setState('limit', $limit);
		$data			=	$gruserob->getItems($options);

		foreach ($data as $val)
		{
			$val->id	=	$val->uid;
		}

		$user_list		=	$mapp->mapItem($data, 'user', $log_user);

		foreach ($user_list as $user)
		{
			$user->isMember			=	$grp_model->isMember($user->id, $clusterId);
			$user->isOwner			=	$grp_model->isOwner($user->id, $clusterId);
			$user->isInvited		=	$grp_model->isInvited($user->id, $clusterId);
			$user->isPendingMember	=	$grp_model->isPendingMember($user->id, $clusterId);
		}

		return $user_list;
	}

	/**
	 * Method join group by user
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function joineGroup()
	{
		// Init variable
		$app		=	JFactory::getApplication();
		$log_user	=	$this->plugin->get('user')->id;
		$clusterId	=	$app->input->get('id', 0, 'INT');
		$type		=	$app->input->get('type', 'group', 'string');

		// Response object
		$res = new stdClass;

		// Get the user's access as we want to limit the number of groups they can join
		$user		=	FD::user($log_user);
		$access		=	$user->getAccess();
		$total		=	$user->getTotalGroups();

		if ($type == 'group')
		{
			$cluster	=	FD::group($clusterId);
		}
		else
		{
			$cluster	= FD::page($clusterId);
		}

		if ($clusterId)
		{
			if ($access->exceeded('groups.join', $total))
			{
				$res->result->status = 0;
				$res->result->message = JText::_('PLG_API_EASYSOCIAL_GROUP_JOIN_LIMIT_EXCEEDS_MESSAGE');

				$this->plugin->setResponse($res);
			}

			if (!$cluster->isMember($log_user))
			{
				// Create a member record for the group
				if ($cluster->type == 3)
				{
					$members	=	$cluster->createMember($log_user, true);
				}
				else
				{
					$members	=	$cluster->createMember($log_user);
				}

				$res->result->status	=	1;
				$res->result->state		=	$members->state;

				if ($cluster->type == 1 && $res->result->state == 1)
				{
					$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_OPEN_GROUP_JOIN_SUCCESS');
				}
				elseif (($cluster->type == 3 || $cluster->type == 2) && $res->result->state == 1)
				{
					$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_GROUP_JOIN_SUCCESS');
				}
				elseif ($res->result->state == 2)
				{
					$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_GROUP_PENDING_APPROVAL');
				}
			}
			else
			{
				$res->result->status	=	0;
				$res->result->state		=	0;
				$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_GROUP_ALREADY_JOINED_MESSAGE');
			}

			$this->plugin->setResponse($res);
		}
	}
}
