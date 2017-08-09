<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugin
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

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/tables/friend.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/friends.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

/**
 * API class EasysocialApiResourceNotification
 *
 * @since  1.0
 */
class EasysocialApiResourceNotification extends ApiResource
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
		$this->plugin->setResponse($this->get_data());
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
		$this->friend_add_remove();
	}

	/**
	 * Method forking respective function.
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function friend_add_remove()
	{
		$app		=	JFactory::getApplication();
		$flag		=	$app->input->get('flag', null, 'STRING');

		if ($flag == 'reject')
		{
			$result1	=	$this->removefriend();

			return $result1;
		}
		elseif ($flag == 'accept')
		{
			$result2	=	$this->addfriend();

			return $result2;
		}
		elseif ($flag == 'cancelrequest')
		{
			$result3	=	$this->requestcancel();

			return $result3;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method cancel friend request
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function requestcancel()
	{
		$app	=	JFactory::getApplication();

		// Getting target id and user id.
		$user	=	$app->input->get('user_id', 0, 'INT');
		$target	=	$app->input->get('target_id', 0, 'INT');

		// Loading friend model for getting id
		$friendmodel	=	FD::model('Friends');
		$state			=	SOCIAL_FRIENDS_STATE_FRIENDS;
		$status			=	$friendmodel->isFriends($user, $target, $state);

		$res = new stdClass;

		if (!$status)
		{
			/* final call to Cancel friend request.
			* $final = $friend->reject();
			*/

			$final	=	ES::friends($target, $user)->cancel();
			$res->result->status = 1;
			$this->plugin->setResponse($res);
		}
		else
		{
			$res->result->status = 0;
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method reject friend request
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function removefriend()
	{
		$app	=	JFactory::getApplication();

		// Getting target id and user id.
		$user	=	$app->input->get('user_id', 0, 'INT');
		$target	=	$app->input->get('target_id', 0, 'INT');
		$friend	=	FD::table('Friend');

		// Loading friend model for getting id.
		$friendmodel	=	FD::model('Friends');
		$state			=	SOCIAL_FRIENDS_STATE_FRIENDS;
		$status			=	$friendmodel->isFriends($user, $target, $state);
		$addstate		=	$friend->loadByUser($user, $target);

		$res = new stdClass;

		if (!$addstate)
		{
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNABLE_REJECT_FRIEND_REQ');
			$res->result->status	=	false;
			$this->plugin->setResponse($res);
		}

		if (!$status)
		{
			// Final call to reject friend request.
			$final	=	ES::friends($target, $user)->reject();
		}
		else
		{
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNABLE_REJECT_FRIEND_REQ');
			$res->result->status	=	false;
			$this->plugin->setResponse($res);
		}

		$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_FRIEND_REQ_CANCEL');
		$res->result->status	=	true;
		$this->plugin->setResponse($res);
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function addfriend()
	{
		$app			=	JFactory::getApplication();
		$user			=	$app->input->get('user_id', 0, 'INT');
		$target			=	$app->input->get('target_id', 0, 'INT');
		$friend			=	FD::table('Friend');

		$state			=	SOCIAL_FRIENDS_STATE_FRIENDS;
		$friendmodel	=	FD::model('Friends');
		$status			=	$friendmodel->isFriends($user, $target, $state);
		$addstate		=	$friend->loadByUser($user, $target);

		$res			=	new stdClass;

		if (!$addstate)
		{
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNBALE_ADD_FRIEND_REQ');
			$res->result->status	=	false;
			$this->plugin->setResponse($res);
		}

		if (!$status)
		{
			$final	=	ES::friends($target, $user)->approve();
		}
		else
		{
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNBALE_ADD_FRIEND_REQ');
			$res->result->status	=	false;
			$this->plugin->setResponse($res);
		}

		$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_FRIEND_REQ_ACCEPT');
		$res->result->status	=	true;
		$this->plugin->setResponse($res);
	}

	/**
	 * Method common function for forking other functions
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get_data()
	{
		$app						=	JFactory::getApplication();
		$uid						=	$app->input->get('uid', 0, 'INT');
		$data						=	array();
		$data['messagecount']		=	$this->get_message_count($uid);
		$data['message']			=	$this->get_messages($uid);
		$data['notificationcount']	=	$this->get_notification_count($uid);
		$data['notifications']		=	$this->get_notifications($uid);
		$data['friendcount']		=	$this->get_friend_count($uid);
		$data['friendreq']			=	$this->get_friend_request($uid);

		return $data;
	}

	/**
	 * Method get_friend_count
	 *
	 * @param   string  $uid  user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_friend_request($uid)
	{
		$object	=	new EasySocialModelFriends;
		$result	=	$object->getPendingRequests($uid);

		return $result;
	}

	/**
	 * Method get_friend_count
	 *
	 * @param   string  $uid  user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_friend_count($uid)
	{
		$model	=	FD::model('Friends');
		$total	=	$model->getTotalRequests($uid);

		return $total;
	}

	/**
	 * Method get_message_count
	 *
	 * @param   string  $uid  user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_message_count($uid)
	{
		$model	=	FD::model('Conversations');
		$total	=	$model->getNewCount($uid, 'user');

		return $total;
	}

	/**
	 * Method get_notification_count
	 *
	 * @param   string  $uid  user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_notification_count($uid)
	{
		$options	=	array(
						'unread' => true,
						'target' => array('id' => $uid, 'type' => SOCIAL_TYPE_USER)
					);
		$model		=	FD::model('Notifications');
		$total		=	$model->getCount($options);

		return $total;
	}

	/**
	 * Method get_messages
	 *
	 * @param   string  $uid  user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_messages($uid)
	{
			$maxlimit	=	0;

			// Get the conversations model
			$model		=	FD::model('Conversations');

			// We want to sort items by latest first
			$options	=	array('sorting' => 'lastreplied', 'maxlimit' => $maxlimit);

			// Get conversation items.
			$conversations	=	$model->getConversations($uid, $options);

			return $conversations;
	}

	/**
	 * Method get_notifications
	 *
	 * @param   string  $uid  user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get_notifications($uid)
	{
		$notification	=	FD::notification();
		$options		=	array('target_id' => $uid,
								'target_type' => SOCIAL_TYPE_USER,
								'unread' => true
								);
			$items		=	$notification->getItems($options);

			return $items;
	}
}
