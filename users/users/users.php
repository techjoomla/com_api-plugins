<?php
/**
 * @package     Com_Api
 * @subpackage  Com_api-plugins
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.user.user');
jimport('joomla.plugin.plugin');
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
		$this->plugin->setResponse(JText::_('PLG_API_USERS_IN_DELETE_FUNCTION_MESSAGE'));
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

		$app 				=	JFactory::getApplication();
		$data['username']	=	$app->input->get('username', '', 'STRING');
		$data['password']	=	$app->input->get('password', '', 'STRING');
		$data['name']		=	$app->input->get('name', '', 'STRING');
		$data['email']		=	$app->input->get('email', '', 'STRING');
		$data['enabled']	=	$app->input->get('enabled', 1, 'INT');
		$data['activation']	=	$app->input->get('activation', 0, 'INT');
		$data['app']		=	$app->input->get('app_name', 'Easysocial App', 'STRING');
		$data['profile_id']	=	$app->input->get('profile_id', 1, 'INT');

		global $message;

		$eobj = new stdClass;

		if ($data['username'] == '' || $data['password'] == '' || $data['name'] == '' || $data['email'] == '')
		{
			$eobj->status = false;
			$eobj->id = 0;
			$eobj->code = '403';
			$eobj->message = JText::_('PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE');
			$this->plugin->setResponse($eobj);

			return;
		}

		jimport('joomla.user.helper');
		$authorize = JFactory::getACL();
		$user = clone JFactory::getUser();
		$user->set('username', $data['username']);
		$user->set('password', $data['password']);
		$user->set('name', $data['name']);
		$user->set('email', $data['email']);
		$user->set('block', $data['enabled']);
		$user->set('activation', $data['activation']);

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

		$date = JFactory::getDate();
		$user->set('registerDate', $date->toSql());

		// True on success, false otherwise
		if (!$user->save())
		{
			// $message = "not created because of " . $user->getError();
			$message = $user->getError();

			$eobj->status	=	false;
			$eobj->id		=	0;
			$eobj->code		=	'403';
			$eobj->message	=	$message;
			$this->plugin->setResponse($eobj);
		}
		else
		{
			/* Update profile type */
			$profiles = FD::model('profiles');
			$all_profiles = $profiles->getAllProfiles();

			foreach ($all_profiles as $key)
			{
				if ($key->id == $data['profile_id'])
				{
					$profiles->updateUserProfile($user->id, $data['profile_id']);
				}
			}

			$mail_sent = $this->sendRegisterEmail($data);

			$easysocial = JPATH_ADMINISTRATOR . '/components/com_easysocial/easysocial.php';

			// Eb version
			if (JFile::exists($easysocial))
			{
				$pobj = $this->createEsprofile($user->id);
				$message = JText::_('PLG_API_USERS_ACCOUNT_CREATED_SUCCESSFULLY_MESSAGE');
			}
			else
			{
				$message = JText::_('PLG_API_USERS_ACCOUNT_CREATED_SUCCESSFULLY_MESSAGE');
			}

			// Assign badge for the person.
			$badge = FD::badges();
			$badge->log('com_easysocial', 'registration.create', $user->id, JText::_('COM_EASYSOCIAL_REGISTRATION_BADGE_REGISTERED'));
		}

		$userid = $user->id;

		$eobj->status = true;
		$eobj->id = $userid;
		$eobj->code = '200';
		$eobj->message = $message;

		$this->plugin->setResponse($eobj);

		return;
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
				$this->plugin->setResponse($this->getErrorResponse(JText::_('PLG_API_USERS_USER_NOT_FOUND_MESSAGE')));

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

	/**
	 * Method  create  profile
	 *
	 * @param   array  $log_user  log_user
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function createEsprofile($log_user)
	{
		$obj = new stdClass;

		if (JComponentHelper::isEnabled('com_easysocial', true))
		{
			$app    = JFactory::getApplication();

			$epost = $app->input->get('fields', '', 'ARRAY');

			require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';

			// Get all published fields apps that are available in the current form to perform validations
			$fieldsModel = FD::model('Fields');

			// Get current user.
			$my = FD::user($log_user);

			// Only fetch relevant fields for this user.
			$options = array(
						'profile_id'	=>	$my->getProfile()->id,
						'data'			=>	true,
						'dataId'		=>	$my->id,
						'dataType'		=>	SOCIAL_TYPE_USER,
						'visible'		=>	SOCIAL_PROFILES_VIEW_EDIT,
						'group'			=>	SOCIAL_FIELDS_GROUP_USER
						);

			$fields = $fieldsModel->getCustomFields($options);

			$epost = $this->create_field_arr($fields, $epost);

			// Load json library.
			$json = FD::json();

			// Initialize default registry
			$registry = FD::registry();

			// Get disallowed keys so we wont get wrong values.
			$disallowed = array(FD::token(), 'option', 'task', 'controller');

			// Process $_POST vars
			foreach ($epost as $key => $value)
			{
				if (!in_array($key, $disallowed))
				{
					if (is_array($value) && $key != 'es-fields-11')
					{
						$value = $json->encode($value);
					}

					$registry->set($key, $value);
				}
			}

			// Convert the values into an array.
			$data = $registry->toArray();

			// Perform field validations here. Validation should only trigger apps that are loaded on the form
			// @trigger onRegisterValidate
			$fieldsLib = FD::fields();

			// Get the general field trigger handler
			$handler = $fieldsLib->getHandler();

			// Build arguments to be passed to the field apps.
			$args = array( $data, &$my );

			// Bind the my object with appropriate data.
			$my->bind($data);

			// Save the user object.
			$sval = $my->save();

			// Reconstruct args
			$args = array(&$data, &$my);

			// @trigger onEditAfterSave
			// $fieldsLib->trigger( 'onEditAfterSave', SOCIAL_FIELDS_GROUP_USER, $fields , $args );
			$fieldsLib->trigger('onRegisterAfterSave', SOCIAL_FIELDS_GROUP_USER, $fields, $args);

			// Bind custom fields for the user.
			$my->bindCustomFields($data);

			// Reconstruct args
			$args = array(&$data, &$my);

			// @trigger onEditAfterSaveFields
			$fieldsLib->trigger('onEditAfterSaveFields', SOCIAL_FIELDS_GROUP_USER, $fields, $args);

			if ($sval)
			{
				$obj->success = 1;
				$obj->message = JText::_('PLG_API_USERS_PROFILE_CREATED_SUCCESSFULLY_MESSAGE');
			}
			else
			{
				$obj->success = 0;
				$obj->message = JText::_('PLG_API_USERS_UNABLE_CREATE_PROFILE_MESSAGE');
			}
		}
		else
		{
			$obj->success = 0;
			$obj->message = JText::_('PLG_API_USERS_EASYSOCIAL_NOT_INSTALL_MESSAGE');
		}

		return $obj;
	}

	/**
	 * Method create field array as per easysocial
	 *
	 * @param   array  $fields  fields 
	 * @param   array  $post    Post
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function create_field_arr($fields, $post)
	{
		$fld_data = array();
		$app = JFactory::getApplication();

		require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

		// For upload photo
		if (!empty($_FILES['avatar']['name']))
		{
			$upload_obj			=	new EasySocialApiUploadHelper;
			$phto_obj			=	$upload_obj->ajax_avatar($_FILES['avatar']);
			$avtar_pth			=	$phto_obj['temp_path'];
			$avtar_scr			=	$phto_obj['temp_uri'];
			$avtar_typ			=	'upload';
			$avatar_file_name	=	$_FILES['avatar']['name'];
		}

		foreach ($fields as $field)
		{
			$fobj = new stdClass;
			$fullname = $app->input->get('name', '', 'STRING');
			$fld_data['first_name'] = $app->input->get('name', '', 'STRING');

			$fobj->first	= $fld_data['first_name'];
			$fobj->middle	= '';
			$fobj->last		= '';
			$fobj->name		= $fullname;

			switch ($field->unique_key)
			{
				case 'HEADER':
				break;
				case 'JOOMLA_FULLNAME':	$fld_data['es-fields-' . $field->id] = $fobj;
								break;
				case 'JOOMLA_USERNAME':	$fld_data['es-fields-' . $field->id] = $app->input->get('username', '', 'STRING');
								break;
				case 'JOOMLA_PASSWORD':	$fld_data['es-fields-' . $field->id] = $app->input->get('password', '', 'STRING');
								break;
				case 'JOOMLA_EMAIL': $fld_data['es-fields-' . $field->id] = $app->input->get('email', '', 'STRING');
								break;
				case 'AVATAR':
								if (isset($avtar_scr))
								{
									$fld_data['es-fields-' . $field->id] = Array
									(
										'source' => $avtar_scr,
										'path' => $avtar_pth,
										'data' => '',
										'type' => $avtar_typ,
										'name' => $avatar_file_name
									);
								}

				break;
			}
		}

		return $fld_data;
	}

	/**
	 * Method use to get RegisterEmail
	 *
	 * @param   string  $base_dt  The  table
	 * 
	 * @return  ApiPlugin response object
	 * 	
	 * @since  1.0
	 */
	public function sendRegisterEmail($base_dt)
	{
		$config = JFactory::getConfig();
		$params = JComponentHelper::getParams('com_users');
		$sendpassword = $params->get('sendpassword', 1);

		$lang = JFactory::getLanguage();
		$lang->load('com_users', JPATH_SITE, '', true);

		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');
		$data['siteurl'] =	JUri::root();
		$data['activation'] = $base_dt['activation'];

		// Handle account activation/confirmation emails.
		if ($data['activation'] == 0)
		{
			// Set the link to confirm the user email.
			$uri = JUri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$base_dt['name'],
				$data['sitename']
			);
			$emailBody = '';

			if ($sendpassword)
			{
				$emailBody = JText::sprintf(
					'Hello %s,\n\nThank you for registering at %s. Your account is created and activated. 
					\nYou can login to %s using the following username and password:\n\nUsername: %s\nPassword: %s',
					$base_dt['name'],
					$data['sitename'],
					$base_dt['app'],
					$base_dt['username'],
					$base_dt['password']
				);
			}
		}
		elseif ($data['activation'] == 1)
		{
			// Set the link to activate the user account.
			$uri = JUri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$base_dt['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
					$base_dt['name'],
					$data['sitename'],
					$data['activate'],
					$base_dt['app'],
					$base_dt['username'],
					$base_dt['password']
				);
			}
		}
		// Send the registration email.
		$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $base_dt['email'], $emailSubject, $emailBody);

		return $return;
	}
}
