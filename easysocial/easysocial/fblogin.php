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
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');

require_once JPATH_SITE . '/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE . '/components/com_api/libraries/authentication/login.php';
require_once JPATH_SITE . '/components/com_api/models/key.php';
require_once JPATH_SITE . '/components/com_api/models/keys.php';
require_once JPATH_SITE . '/components/com_api/models/keys.php';
/**
 * API class EasysocialApiResourceFblogin
 *
 * @since  1.0
 */
class EasysocialApiResourceFblogin extends ApiResource
{
	public $provider = '';

	public $accessToken = '';

	public $socialProfData = '';

	public $confirmed = '';

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function get()
	{
		$this->plugin->setResponse(JText::_('PLG_API_EASYSOCIAL_UNSUPPORTED_METHOD_MESSAGE'));
	}

	/**
	 * Method description
	 *
	 * @return boolean
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function post()
	{
		$app = JFactory::getApplication();
		$this->accessToken = $app->input->get('access_token', '', 'STR');
		$this->provider = $app->input->get('provider', 'facebook', 'STR');
		$this->confirmed = $app->input->get('confirmed', '0', 'INT');
		$obj = new stdClass;

		$this->is_use_jfb = JComponentHelper::isEnabled('com_jfbconnect', true);

		if ($this->accessToken)
		{
			$objFbProfileData = $this->jfbGetUser($this->accessToken);
			$userId = $this->jfbGetUserFromMap($objFbProfileData);

			if ($this->confirmed == 0 && ! isset($objFbProfileData->birthday) && ! ($userId > 0))
			{
				$obj->code = 200;
				$obj->warning = JText::_('PLG_API_FB_CON_DATE_NOT_FOUND');
				$this->plugin->setResponse($obj);

				return false;
			}

			if ($userId)
			{
				$this->jfbLogin($userId);
			}
			else
			{
				if (! isset($objFbProfileData->email))
				{
					$reg_dt = new stdClass;
					$reg_dt->code = 200;
					$reg_dt->email = 0;
					$reg_dt->warning = 'Email id not found in Facebook data unable to register user, Do you want to complete registration process';
					$reg_dt->data = $objFbProfileData;

					$this->plugin->setResponse($reg_dt);

					return false;
				}

				$this->jfbRegister($this->accessToken);
				$userId = $this->jfbGetUserFromMap($objFbProfileData);

				if ($userId)
				{
					$this->jfbLogin($userId);
				}
			}
		}
		else
		{
			$this->badRequest(JText::_('PLG_API_EASYSOCIAL_BAD_REQUEST'));
		}
	}

	/**
	 * Method description
	 *
	 * @param   string  $mesage  message
	 * @param   int     $code    code
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function badRequest($mesage, $code)
	{
		$code = $code ? $code : 403;
		$obj = new stdClass;
		$obj->code = $code;
		$obj->message = $mesage;
		$this->plugin->setResponse($obj);
	}

	/**
	 * Method description
	 *
	 * @param   int  $userId  id
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function jfbLogin($userId)
	{
		$this->plugin->setResponse($this->keygen($userId));
	}

	/**
	 * Method description
	 *
	 * @param   string  $accessToken  tocken
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function jfbRegister($accessToken)
	{
		$objFbProfileData = $this->jfbGetUser($accessToken);

		if ($objFbProfileData->id)
		{
			$username = $objFbProfileData->name;

			if ($username)
			{
				$isUserExist = 1;
				$loopCounter = 0;

				while ($isUserExist)
				{
					$isUserExist = $this->checkUserNameIsExist($username);

					if ($isUserExist)
					{
						$username .= '_' . $loopCounter ++;
					}
				}

				$userId = $this->joomlaCreateUser($username, $objFbProfileData->name, $objFbProfileData->email, $objFbProfileData);

				if ($userId && $this->is_use_jfb)
				{
					$this->jfbCreateUser($userId, $objFbProfileData);
				}
			}
		}
	}

	/**
	 * Method function to get user from #_jfbconnect_user_map
	 *
	 * @param   unknown  $objFbProfileData  FB object data
	 * 
	 * @return number
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function jfbGetUserFromMap($objFbProfileData)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$userId = 0;

		if ($this->is_use_jfb)
		{
			$query->select(' j_user_id ');
			$query->from('#__jfbconnect_user_map');
			$query->where(" provider_user_id = " . $db->quote($objFbProfileData->id));
			$db->setQuery($query);
			$userId = $db->loadResult();
		}

		if (! $userId)
		{
			$query = $db->getQuery(true);
			$query->select(' id ');
			$query->from('#__users');
			$query->where(" email = " . $db->quote($objFbProfileData->email));
			$db->setQuery($query);
			$userId = $db->loadResult();
		}

		return $userId;
	}

	/**
	 * Method function to get user from #_jfbconnect_user_map
	 *
	 * @param   unknown  $accessToken  fb tocken
	 * 
	 * @return mixed
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function jfbGetUser($accessToken)
	{
		$url = 'https://graph.facebook.com/v2.2/me';
		$token_params = array(
			"access_token" => $accessToken, "fields" => 'id,name,gender,email,location,website,picture,relationship_status'
		);

		return $this->makeRequest($url, $token_params);
	}

	/**
	 * Method function to get user from #_jfbconnect_user_map
	 *
	 * @param   unknown  $jUserId           user id
	 * @param   unknown  $objFbProfileData  object Data
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function jfbCreateUser($jUserId, $objFbProfileData)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Manupulate paramteres to save
		$params = array(
			'profile_url' => 'https://www.facebook.com/app_scoped_user_id/' . $objFbProfileData->id,
				'avatar_thumb' => $objFbProfileData->picture->data->url
		);

		$columns = array(
			'j_user_id', 'provider_user_id', 'created_at', 'updated_at', 'access_token', 'authorized', 'params', 'provider'
		);

		// Insert values.
		$values = array(
			$jUserId, $objFbProfileData->id, $db->quote(date('Y-m-d H:i:s')), $db->quote(date('Y-m-d H:i:s')),
				$db->quote('"' . $this->accessToken . '"'), 1, $db->quote(json_encode($params)), $db->quote($this->provider)
		);

		// Prepare the insert query.
		$query->insert($db->quoteName('#__jfbconnect_user_map'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		// Set the query using our newly populated query object and execute it.
		$db->setQuery($query);

		return $result = $db->execute();
	}

	/**
	 * Method description
	 *
	 * @param   unknown  $url     url
	 * @param   unknown  $params  params
	 *  
	 * @return mixed
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function makeRequest($url, $params)
	{
		$url .= '?' . http_build_query($params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_USERAGENT, '');
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		$output = json_decode($output);

		return $output;
	}

	/**
	 * Method public function for genrate key
	 *
	 * @param   unknown  $userId  user id
	 * 
	 * @return stdClass
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function keygen($userId)
	{
		$kmodel = new ApiModelKey;
		$key = null;

		// Get login user hash
		$kmodel->setState('user_id', $userId);
		$log_hash = $kmodel->getList();
		$log_hash = $log_hash[count($log_hash) - count($log_hash)];
		$obj = new stdClass;

		if ($log_hash->hash)
		{
			$key = $log_hash->hash;
		}
		elseif ($key == null || empty($key))
		{
			// Create new key for user
			$data = array(
				'userid' => $userId, 'domain' => '', 'state' => 1, 'id' => '', 'task' => 'save', 'c' => 'key',
					'ret' => 'index.php?option=com_api&view=keys', 'option' => 'com_api', JSession::getFormToken() => 1
			);
			$result = $kmodel->save($data);
			$key = $result->hash;
		}

		if (! empty($key))
		{
			$obj->auth = $key;
			$obj->code = '200';
			$obj->id = $userId;
		}
		else
		{
			$this->badRequest(JText::_('PLG_API_EASYSOCIAL_BAD_REQUEST'));
		}

		return ($obj);
	}

	/**
	 * Method description
	 *
	 * @param   unknown  $username  username
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function checkUserNameIsExist($username)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select(' id ');
		$query->from('#__users');
		$query->where(" username = " . $db->quote($username));
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Method function to create new joomla user
	 * 
	 * @param   unknown  $username          username 
	 * @param   unknown  $name              name
	 * @param   unknown  $email             email
	 * @param   unknown  $objFbProfileData  profile data
	 * 
	 * @return boolean
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function joomlaCreateUser($username, $name, $email, $objFbProfileData)
	{
		$error_messages = array();
		$fieldname = array();
		$response = null;
		$validated = true;
		$userid = null;
		$data = array();

		$app = JFactory::getApplication();
		$data['username'] = $username;
		$data['password'] = JUserHelper::genRandomPassword(8);
		$data['name'] = $name;
		$data['email'] = $email;
		$data['enabled'] = 0;
		$data['activation'] = 0;

		global $message;
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
		$salt = JUserHelper::genRandomPassword(32);
		$crypt = JUserHelper::getCryptedPassword($user->password, $salt);
		$user->password = "$crypt:$salt";

		// User group/type
		$user->set('id', '');
		$user->set('usertype', 'Registered');

		if (JVERSION >= '1.6.0')
		{
			$userConfig = JComponentHelper::getParams('com_users');

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
		if (! $user->save())
		{
			$user->getError();

			return false;
		}
		else
		{
			$mail_sent = $this->sendRegisterEmail($data);

			$userid = $user->id;

			if ($userid)
			{
				$username = explode(" ", $objFbProfileData->name);
				$firstName = $username[0];
				$lastName = $username[1];

				$esocialProfData = array(
					'BIRTHDAY' => isset($objFbProfileData->birthday) ? $objFbProfileData->birthday : '', 'last_name' => $lastName,
						'first_name' => $firstName, 'GENDER' => $objFbProfileData->gender == 'male' ? 1 : 0
				);
				$easysocial = JPATH_ADMINISTRATOR . '/components/com_easysocial/easysocial.php';

				// EB version
				if (JFile::exists($easysocial))
				{
					$pobj = $this->createEsprofile($user->id, $esocialProfData);
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
		}
	}

	/**
	 * Method Function create easysocial profile.
	 *
	 * @param   unknown  $log_user  userid
	 * @param   unknown  $fields    userfields
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function createEsprofile($log_user, $fields)
	{
		$obj = new stdClass;

		if (JComponentHelper::isEnabled('com_easysocial', true))
		{
			$app = JFactory::getApplication();
			$epost = $fields;

			require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';

			// Get all published fields apps that are available in the current form to perform validations
			$fieldsModel = FD::model('Fields');

			// Get current user.
			$my = FD::user($log_user);

			// Only fetch relevant fields for this user.
			$options = array(
				'profile_id' => $my->getProfile()->id, 'data' => true, 'dataId' => $my->id, 'dataType' => SOCIAL_TYPE_USER,
					'visible' => SOCIAL_PROFILES_VIEW_EDIT, 'group' => SOCIAL_FIELDS_GROUP_USER
			);

			$fields = $fieldsModel->getCustomFields($options);

			$epost = $this->create_field_arr($fields, $epost);

			// Load json library.
			$json = FD::json();

			// Initialize default registry
			$registry = FD::registry();

			// Get disallowed keys so we wont get wrong values.
			$disallowed = array(
				FD::token(), 'option', 'task', 'controller'
			);

			// Process $_POST vars
			foreach ($epost as $key => $value)
			{
				if (! in_array($key, $disallowed))
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
			$args = array(
				$data, &$my
			);

			// Bind the my object with appropriate data.
			$my->bind($data);

			// Save the user object.
			$sval = $my->save();

			// Reconstruct args
			$args = array(
				&$data, &$my
			);

			// @trigger onEditAfterSave
			$fieldsLib->trigger('onEditAfterSave', SOCIAL_FIELDS_GROUP_USER, $fields, $args);

			// Bind custom fields for the user.
			$my->bindCustomFields($data);

			// Reconstruct args
			$args = array(
				&$data, &$my
			);

			// @trigger onEditAfterSaveFields
			$fieldsLib->trigger('onEditAfterSaveFields', SOCIAL_FIELDS_GROUP_USER, $fields, $args);
		}
	}

	/**
	 * Method create field array as per easysocial
	 *
	 * @param   unknown  $fields  data to create fields
	 * @param   unknown  $post    post data
	 * 
	 * @return unknown[]|string[]|stdClass[]|NULL[]|unknown[][]|NULL[][]|string[][]
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function create_field_arr($fields, $post)
	{
		$fld_data = array();
		$app = JFactory::getApplication();

		require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

// For upload photo
		if (! empty($_FILES['avatar']['name']))
		{
			$upload_obj = new EasySocialApiUploadHelper;

			// Ckecking upload cover
			$phto_obj = $upload_obj->ajax_avatar($_FILES['avatar']);
			$avtar_pth = $phto_obj['temp_path'];
			$avtar_scr = $phto_obj['temp_uri'];
			$avtar_typ = 'upload';
			$avatar_file_name = $_FILES['avatar']['name'];
		}

		foreach ($fields as $field)
		{
			$fobj = new stdClass;
			$fullname = $post['TITLE'] . " " . $post['first_name'] . " " . $post['middle_name'] . " " . $post['last_name'];
			$address = $post['STREET_1'] . "," . $post['STREET_2'] . "," . $post['CITY'] . "," . $post['PIN_CODE'] . ","
						. $post['STATE'] . "," . $post['COUNTRY'];

			// Code for address comma remove
			if ($address == ',,,,,')
			{
				$address = '';
			}

			$fld_data['first_name'] = (! empty($post['first_name'])) ? $post['first_name'] : $app->input->get('name', '', 'STRING');
			$fld_data['middle_name'] = $post['middle_name'];
			$fld_data['last_name'] = $post['last_name'];

			$fobj->first = $post['TITLE'] . " " . $fld_data['first_name'];
			$fobj->middle = $post['middle_name'];
			$fobj->last = $post['last_name'];
			$fobj->name = $fullname;

			switch ($field->unique_key)
			{
				case 'HEADER':
					break;

				case 'JOOMLA_FULLNAME':
					$fld_data['es-fields-' . $field->id] = $fobj;
					break;
				case 'JOOMLA_USERNAME':
					$fld_data['es-fields-' . $field->id] = $app->input->get('username', '', 'STRING');
					break;
				case 'JOOMLA_PASSWORD':
					$fld_data['es-fields-' . $field->id] = $app->input->get('password', '', 'STRING');
					break;
				case 'JOOMLA_EMAIL':
					$fld_data['es-fields-' . $field->id] = $app->input->get('email', '', 'STRING');
					break;
				case 'JOOMLA_TIMEZONE':
					$fld_data['es-fields-' . $field->id] = isset($post['timezone']) ? $post['timezone'] : null;
					break;
				case 'JOOMLA_USER_EDITOR':
					$fld_data['es-fields-' . $field->id] = isset($post['editor']) ? $post['editor'] : null;
					break;

				case 'PERMALINK':
					$fld_data['es-fields-' . $field->id] = isset($post['permalink']) ? $post['permalink'] : null;
					break;
				case 'BIRTHDAY':
					$bod = array();

					if (isset($post['BIRTHDAY']))
					{
						$config = JFactory::getConfig();
						$bod['date'] = $post['BIRTHDAY'];
						$bod['timezone'] = $config->get('offset');
					}

					$fld_data['es-fields-' . $field->id] = isset($post['BIRTHDAY']) ? $bod : array();
					break;
				case 'GENDER':
					$fld_data['es-fields-' . $field->id] = isset($post['GENDER']) ? $post['GENDER'] : '';
					break;
				case 'ADDRESS':
					$fld_data['es-fields-' . $field->id] = $address;
					break;
				case 'TEXTBOX':
					$fld_data['es-fields-' . $field->id] = $post['MOBILE'];
					break;
				case 'URL':
					$fld_data['es-fields-' . $field->id] = (isset($post['WEBSITE'])) ? $post['WEBSITE'] : '';
					break;

				case 'AVATAR':
					$fld_data['es-fields-' . $field->id] = Array(
						'source' => $avtar_scr, 'path' => $avtar_pth, 'data' => '', 'type' => $avtar_typ, 'name' => $avatar_file_name
					);
					break;
			}
		}

		return $fld_data;
	}

	/**
	 * Method send registration mail
	 *
	 * @param   unknown  $base_dt  string
	 *
	 * @return  mixed
	 *
	 * @since 1.0
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
		$data['siteurl'] = JUri::root();
		$data['activation'] = $base_dt['activation'];

		// Handle account activation/confirmation emails.
		if ($data['activation'] == 0)
		{
			// Set the link to confirm the user email.
			$uri = JUri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $base_dt['name'], $data['sitename']);

			if ($sendpassword)
			{
				$emailBody = JText::sprintf(
											'Hello %s,\n\nThank you for registering at %s. Your account is created and activated.
											\nYou can login to %s using the following username and password:\n\nUsername: %s\nPassword: %s', $base_dt['name'], $data['sitename'],
											$base_dt['app'], $base_dt['username'], $base_dt['password']
											);
			}
		}
		elseif ($data['activation'] == 1)
		{
			// Set the link to activate the user account.
			$uri = JUri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = JText::sprintf('COM_USERS_EMAIL_ACCOUNT_DETAILS', $base_dt['name'], $data['sitename']);

			if ($sendpassword)
			{
				$emailBody = JText::sprintf(
											'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY', $base_dt['name'], $data['sitename'],
											$data['activate'], $base_dt['app'], $base_dt['username'], $base_dt['password']
											);
			}
		}
		// Send the registration email.

		return $return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $base_dt['email'], $emailSubject, $emailBody);
	}
}
