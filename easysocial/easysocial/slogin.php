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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;


BaseDatabaseModel::addIncludePath(JPATH_SITE . 'components/com_api/models');
require_once JPATH_SITE . '/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE . '/components/com_api/libraries/authentication/login.php';
require_once JPATH_SITE . '/components/com_api/models/key.php';
require_once JPATH_SITE . '/components/com_api/models/keys.php';

require_once JPATH_SITE . '/libraries/joomla/filesystem/folder.php';
require_once JPATH_ROOT . '/administrator/components/com_users/models/users.php';

/**
 * API class EasysocialApiResourceSlogin
 *
 * @since  1.0
 */
class EasysocialApiResourceSlogin extends ApiResource
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
		$this->plugin->setResponse(Text::_('PLG_API_EASYSOCIAL_UNSUPPORTED_METHOD_MESSAGE'));
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
		$this->plugin->setResponse($this->keygen());
	}

	/**
	 * Method Public function for genrate key
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function keygen()
	{
		$app          = Factory::getApplication();
		$user_details = 0;

		// Code for social login
		$slogin       = $app->input->get('social_login', 0, 'INT');

		if ($slogin)
		{
			$user_id   = $app->input->get('user_id', 0, 'INT');
			$tokan     = $app->input->get('tokan', 0, 'STRING');
			$username  = $app->input->get('username', '', 'STRING');
			$name      = $app->input->get('name', '', 'STRING');
			$email_crp = $app->input->get('email', '', 'STRING');
			$email_crp = base64_decode($email_crp);
			$email     = str_replace($tokan, '', $email_crp);
			$reg_usr   = 0;

			if ($email)
			{
				$reg_usr = $this->check_user($email);

				if ($reg_usr == null)
				{
					$user_details = $this->createUser($username, $name, $email);
					$reg_usr      = $user_details['user_id'];
				}
			}

			$user = Factory::getUser($reg_usr);
		}
		else
		{
			// Init variable
			$obj    = new stdclass;
			$umodel = new User;
			$user   = $umodel->getInstance();

			if (!$user->id)
			{
				$user = Factory::getUser($this->plugin->get('user')->id);
			}
		}

		if (!$user->id)
		{
			$obj->code    = 403;
			$obj->message = Text::_('PLG_API_EASYSOCIAL_INVALID_USER');

			return $obj;
		}

		$kmodel = new ApiModelKey;
		$model  = new ApiModelKeys;
		$key    = null;

		// Get login user hash
		$kmodel->setState('user_id', $user->id);
		$log_hash = $kmodel->getList();
		$log_hash = $log_hash[count($log_hash) - count($log_hash)];

		if ($log_hash->hash)
		{
			$key = $log_hash->hash;
		}
		elseif ($key == null || empty($key))
		{
			// Create new key for user
			$data = array(
							'userid' => $user->id,
							'domain' => '',
							'state' => 1,
							'id' => '',
							'task' => 'save',
							'c' => 'key',
							'ret' => 'index.php?option=com_api&view=keys',
							'option' => 'com_api',
							Session::getFormToken() => 1
					);
			$result = $kmodel->save($data);
			$key    = $result->hash;

			// Add new key in easysocial table
			$easyblog = JPATH_ROOT . '/administrator/components/com_easyblog/easyblog.php';

			if (File::exists($easyblog) && ComponentHelper::isEnabled('com_easysocial', true))
			{
				$this->updateEauth($user, $key);
			}
		}

		if (!empty($key))
		{
			$obj->auth = $key;
			$obj->code = '200';
			$obj->id   = $user->id;
		}
		else
		{
			$obj->code    = 403;
			$obj->message = Text::_('PLG_API_EASYSOCIAL_BAD_REQUEST');
		}

		return ($obj);
	}

	/**
	 * function to get joomla user id on email
	 *
	 * @param   string  $email  email address
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function check_user($email)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('u.id');
		$query->from('#__users AS u');
		$query->where(" u.email =" . "'" . $email . "'");
		$db->setQuery($query);

		return $user_id = $db->loadResult();
	}

	/**
	 * Method function to update Easyblog auth keys
	 *
	 * @param   object  $user  User object
	 * @param   string  $key   key
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function updateEauth($user = null, $key = null)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
		$model       = FD::model('Users');
		$id          = $model->getUserId('username', $user->username);
		$user        = FD::user($id);
		$user->alias = $user->username;
		$user->auth  = $key;
		$user->store();

		return $id;
	}

	/**
	 * create User
	 *
	 * @param   string  $username  User name
	 * @param   string  $name      name
	 * @param   string  $email     email address
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function createUser($username, $name, $email)
	{
		$error_messages = array();
		$fieldname      = array();
		$response       = null;
		$validated      = true;
		$userid         = null;
		$data           = array();

		$app              = Factory::getApplication();
		$data['username'] = $username;
		$data['password'] = UserHelper::genRandomPassword(8);
		$data['name']     = $name;
		$data['email']    = $email;

		global $message;
		$authorize = Factory::getACL();
		$user      = clone Factory::getUser();
		$user->set('username', $data['username']);
		$user->set('password', $data['password']);
		$user->set('name', $data['name']);
		$user->set('email', $data['email']);

		// Password encryption
		$salt           = UserHelper::genRandomPassword(32);
		$crypt          = UserHelper::getCryptedPassword($user->password, $salt);
		$user->password = "$crypt:$salt";

		// User group/type
		$user->set('id', '');
		$user->set('usertype', 'Registered');

		if (JVERSION >= '1.6.0')
		{
			$userConfig = ComponentHelper::getParams('com_users');

			// Default to Registered.
			$defaultUserGroup = $userConfig->get('new_usertype', 2);
			$user->set('groups', array($defaultUserGroup));
		}
		else
		{
			$user->set('gid', $authorize->get_group_id('', 'Registered', 'ARO'));
		}

		$date =Factory::getDate();
		$user->set('registerDate', $date->toSql());

		// True on success, false otherwise
		if (!$user->save())
		{
			$message = Text::_('PLG_API_EASYSOCIAL_NOT_CREATED') . $user->getError();

			return false;
		}
		else
		{
			$message = Text::_('PLG_API_EASYSOCIAL_CREATED_USERNAME') . $user->username . Text::_('PLG_API_EASYSOCIAL_SEND_MAIL_DETAILS');
		}

		$userid = $user->id;

		// Result message
		$result = array(
						'user id ' => $userid,
						'message' => $message
					);
		$result = ($userid) ? $result : $message;

		// $this->plugin->setResponse($result);

		return $result;
	}
}
