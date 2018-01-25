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

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/friends.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/avatars.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceFriend
 *
 * @since  1.0
 */
class EasysocialApiResourceFriend extends ApiResource
{
	/**
	 * Method Get
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->getFriends();
	}

	/**
	 * Method post
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->manageFriends();
	}

	/**
	 * Method Function for delete friend from list 
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$this->deletefriend();
	}

	/**
	 * Method used to delete friend or unfriend.
	 * 
	 * @return	stdClass	in success object will return
	 *
	 * @since 1.0
	 */
	public function deletefriend()
	{
		$app			=	JFactory::getApplication();

		// Get target user.
		$frnd_id		=	$app->input->get('target_userid', 0, 'INT');

		// Getting log user.
		$log_user		=	$this->plugin->get('user')->id;
		$res			=	new stdClass;

		// Try to load up the friend table
		$friend_table	=	FD::table('Friend');

		// Load user table.
		$state			=	$friend_table->loadByUser($log_user, $frnd_id);

		// API validations.
		if (!$state)
		{
			$res->result->status	=	0;
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNABLE_DELETE_FRIEND_MESSAGE');

			$this->plugin->setResponse($res);
		}

		//  Throw errors when there's a problem removing the friends
		if (!$friend_table->unfriend($log_user))
		{
			$res->result->status	=	0;
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNABLE_DELETE_FRIEND_MESSAGE');
		}
		else
		{
			$res->result->status	=	1;
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_FRIEND_DELETED_MESSAGE');

			$this->plugin->setResponse($res);
		}
	}

	/**
	 * Method function use for get friends data
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getFriends()
	{
		// Init variable
		$app		=	JFactory::getApplication();
		$user		=	JFactory::getUser($this->plugin->get('user')->id);
		$userid		=	$app->input->get('target_user', $this->plugin->get('user')->id, 'INT');
		$search		=	$app->input->get('search', '', 'STRING');
		$mapp		=	new EasySocialApiMappingHelper;
		$res		=	new stdClass;

		if ($userid == 0)
		{
			$userid		=	$user->id;
		}

		$frnd_mod	=	new EasySocialModelFriends;

		// If search word present then search user as per term and given id
		if (empty($search))
		{
			$ttl_list	=	$frnd_mod->getFriends($userid);
		}
		else
		{
			$ttl_list	=	$frnd_mod->search($userid, $search, 'username');
		}

		$res->result	=	$mapp->mapItem($ttl_list, 'user', $userid);

		//  Get other data
		foreach ($frnd_list as $ky => $lval)
		{
			$lval->mutual	=	$frnd_mod->getMutualFriendCount($user->id, $lval->id);
			$lval->isFriend	=	$frnd_mod->isFriends($user->id, $lval->id);
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method this common function is for getting dates for month,year,today,tomorrow filters.
	 *
	 * @param   string  $data  data
	 * 
	 * @return	integer|array|stdclass[]	in success Array will return, in failure integer
	 *
	 * @since 1.0
	 */
	public function basefrndObj($data=null)
	{
		if ($data == null)
		{
			return 0;
		}

		$list	=	array();

		foreach ($data as $k => $node)
		{
			$obj			=	new stdclass;
			$obj->id		=	$node->id;
			$obj->name		=	$node->name;
			$obj->username	=	$node->username;
			$obj->email		=	$node->email;

			foreach ($node->avatars As $ky => $avt)
			{
				$avt_key		=	'avtar_' . $ky;
				$obj->$avt_key	=	JURI::root() . 'media/com_easysocial/avatars/users/' . $node->id . '/' . $avt;
			}

			$list[]	=	$obj;
		}

		return $list;
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return  stdClass
	 *
	 * @since 1.0
	 */
	public function manageFriends()
	{
		//  Init variable
		$app		=	JFactory::getApplication();
		$log_user	=	JFactory::getUser($this->plugin->get('user')->id);
		$db			=	JFactory::getDbo();
		$frnd_id	=	$app->input->get('target_userid', 0, 'INT');
		$userid		=	$log_user->id;
		$result 	=	new stdClass;
		$res		=	new stdClass;

		if (!$frnd_id)
		{
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_FRIEND_ID_NOT_FOUND');

			$this->plugin->setResponse($res);
		}

		$frnds_obj	=	new EasySocialModelFriends;
		$result		=	$frnds_obj->request($userid, $frnd_id);

		if ($result->id)
		{
			$res->result->status = 1;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_FRIEND_REQUEST_SENT_SUCCESSFULL');
		}
		else
		{
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_FRIEND_SENT_FRIEND_REQ');
		}

		$this->plugin->setResponse($res);
	}
}
