<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trading
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.user.user');
jimport('joomla.plugin.plugin');
//jimport('joomla.html.html');
jimport('joomla.user.helper');
jimport('joomla.application.component.helper');
jimport('joomla.application.component.model');
jimport('joomla.database.table.user');

require_once JPATH_SITE . '/libraries/joomla/filesystem/folder.php';
require_once JPATH_ROOT . '/administrator/components/com_users/models/users.php';

/**
 * User Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.0
 */
class UsersApiResourceUsers extends ApiResource
{
	/**
	 * Function delete for user record.
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->plugin->setResponse('in delete');
	}

	/**
	 * Function post for create user record.
	 *
	 * @return void
	 */
	public function post()
	{
		$error_messages = array();
		$fieldname      = array();
		$response       = null;
		$validated      = true;
		$userid         = null;
		$data           = array();

		$app              = JFactory::getApplication();
		$data['username'] = $app->input->get('username', '', 'STRING');
		$data['password'] = $app->input->get('password', '', 'STRING');
		$data['name']     = $app->input->get('name', '', 'STRING');
		$data['email']    = $app->input->get('email', '', 'STRING');

		global $message;
		jimport('joomla.user.helper');
		$authorize = JFactory::getACL();
		$user = clone JFactory::getUser();
		$user->set('username', $data['username']);
		$user->set('password', $data['password']);
		$user->set('name', $data['name']);
		$user->set('email', $data['email']);

		// Password encryption
		$salt           = JUserHelper::genRandomPassword(32);
		$crypt          = JUserHelper::getCryptedPassword($user->password, $salt);
		$user->password = "$crypt:$salt";

		// User group/type
		$user->set('id', '');
		$user->set('usertype', 'Registered');

		if (JVERSION >= '1.6.0')
		{
			$userConfig       = JComponentHelper::getParams('com_users');

			// Default to Registered.
			$defaultUserGroup = $userConfig->get('new_usertype', 2);
			$user->set('groups', array($defaultUserGroup));
		}
		else
		{
			$user->set('gid', $authorize->get_group_id('', 'Registered', 'ARO'));
		}

		$date =& JFactory::getDate();
		$user->set('registerDate', $date->toSql());

		// True on success, false otherwise
		if (!$user->save())
		{
			$message = "not created because of " . $user->getError();

			return false;
		}
		else
		{
			$message = "created of username-" . $user->username . " and send mail of details please check";
		}

		// #$this->plugin->setResponse($user->id);
		$userid = $user->id;

		// Result message
		$result = array('user id ' => $userid, 'message' => $message);
		$result = ($userid) ? $result : $message;

		$this->plugin->setResponse($result);
	}

	/**
	 * Function get for users record.
	 *
	 * @return void
	 */
	public function get()
	{
		$input = JFactory::getApplication()->input;

		// If we have an id try to fetch the user
		if ($id = $input->get('id'))
		{
			$user = JUser::getInstance($id);

			if (!$user->id)
			{
				$this->plugin->setResponse($this->getErrorResponse(404, 'User not found'));

				return;
			}

			$this->plugin->setResponse($user);
		}
		else
		{
			$model = new UsersModelUsers;
			$users = $model->getItems();

			foreach ($users as $k => $v)
			{
				unset($users[$k]->password);
			}

			$this->plugin->setResponse($users);
		}
	}
}
