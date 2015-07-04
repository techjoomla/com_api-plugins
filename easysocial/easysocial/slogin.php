<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');
jimport('joomla.application.component.helper');
jimport('joomla.database.table.user');

JModelLegacy::addIncludePath(JPATH_SITE.'components/com_api/models');
require_once JPATH_SITE.'/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE.'/components/com_api/libraries/authentication/login.php';
require_once JPATH_SITE.'/components/com_api/models/key.php';
require_once JPATH_SITE.'/components/com_api/models/keys.php';

require_once JPATH_SITE . '/libraries/joomla/filesystem/folder.php';
require_once JPATH_ROOT . '/administrator/components/com_users/models/users.php';

class EasysocialApiResourceSlogin extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse("unsupported method,please use post method");
	}

	public function post()
	{
		$this->plugin->setResponse($this->keygen());
	}
	//public function for genrate key
	public function keygen()
	{
		$app  = JFactory::getApplication();
		$user_details = 0;
		//code for social login
		$slogin = $app->input->get('social_login', 0 , 'INT');
		if($slogin)
		{
			//$provider = $app->input->get('provider', 'facebook' , 'STRING');
			
			$user_id = $app->input->get('user_id',0,'INT');
			$tokan = $app->input->get('tokan',0,'STRING');
			$username = $app->input->get('username','','STRING');
			$name = $app->input->get('name','','STRING');
			$email_crp = $app->input->get('email','','STRING');
			
			//$crpt = base64_encode($email_crp.$tokan);

			$email_crp = base64_decode( $email_crp );
			$email = str_replace( $tokan,'', $email_crp );

			$reg_usr = 0;
			
			if($email )
			{
				$reg_usr = $this->check_user($email);
//print_r( $_POST );
//var_dump( $reg_usr );
//die("in slogin api");
				if( $reg_usr == null )
				{
					$user_details = $this->createUser($username,$name,$email);
					$reg_usr = $user_details['user_id'];
				}
				
			}
			$user = JFactory::getUser( $reg_usr );
		}
		else
		{
			//init variable
			$obj = new stdclass;
			$umodel = new JUser;
			$user = $umodel->getInstance();

			if( !$user->id )
			{
				$user = JFactory::getUser($this->plugin->get('user')->id);
			}
		}
		
		if(!$user->id)
		{
			$obj->code = 403;
			$obj->message = 'Invalid User';
			return $obj;
		}
		
		$kmodel = new ApiModelKey;
		$model = new ApiModelKeys;
		$key = null;
		// Get login user hash
		$kmodel->setState('user_id', $user->id);
		$log_hash = $kmodel->getList();
		$log_hash = $log_hash[count($log_hash) - count($log_hash)];

		if( $log_hash->hash )
		{
			$key = $log_hash->hash;
		}
		elseif( $key == null || empty($key) )
		{
				// Create new key for user
				$data = array(
				'userid' => $user->id,
				'domain' => '' ,
				'state' => 1,
				'id' => '',
				'task' => 'save',
				'c' => 'key',
				'ret' => 'index.php?option=com_api&view=keys',
				'option' => 'com_api',
				JSession::getFormToken() => 1
				);

				$result = $kmodel->save($data);
				$key = $result->hash;
				
				//add new key in easysocial table
				$easyblog = JPATH_ROOT . '/administrator/components/com_easyblog/easyblog.php';
				if (JFile::exists($easyblog) && JComponentHelper::isEnabled('com_easysocial', true))
				{
					$this->updateEauth( $user , $key );
				}
		}
		
			if( !empty($key) )
			{
				$obj->auth = $key;
				$obj->code = '200';
				$obj->id = $user->id;
			}
			else
			{
				$obj->code = 403;
				$obj->message = 'Bad request';
			}
			return( $obj );
	
	}
	
	
	/*
	 * function to get joomla user id on email
	 */
	public function check_user( $email )
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		$query->select('u.id');
		$query->from('#__users AS u');
		$query->where( " u.email ="."'".$email."'");
		$db->setQuery($query);

		return $user_id = $db->loadResult();
	}
	
	/*
	 * function to update Easyblog auth keys
	 */
	public function updateEauth($user=null,$key=null)
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
		$model 	= FD::model('Users');
		$id 	= $model->getUserId('username', $user->username);
		$user 	= FD::user($id);
		$user->alias = $user->username;
		$user->auth = $key;
		$user->store();
	
		return $id;
	}
	/*
	 * function to create new joomla user
	 */
	public function createUser($username,$name,$email)
	{
		
		$error_messages = array();
		$fieldname      = array();
		$response       = null;
		$validated      = true;
		$userid         = null;
		$data           = array();

		$app              = JFactory::getApplication();
		$data['username'] = $username;
		$data['password'] = JUserHelper::genRandomPassword(8);
		$data['name']     = $name;
		$data['email']    = $email;

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

		//$this->plugin->setResponse($result);
		return $result;
	}
}
