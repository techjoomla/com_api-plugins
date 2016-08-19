<?php
/**
 * @  package API plugins
 * @  copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @  license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @  link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');
jimport('joomla.application.component.helper');

JModelLegacy::addIncludePath(JPATH_SITE . 'components/com_api/models');
require_once JPATH_SITE . '/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE . '/components/com_api/libraries/authentication/login.php';
require_once JPATH_SITE . '/components/com_api/models/key.php';
require_once JPATH_SITE . '/components/com_api/models/keys.php';

class UsersApiResourceLogin extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse(JText::_('PLG_API_USERS_GET_METHOD_NOT_ALLOWED_MESSAGE'));
	}

	public function post()
	{
		$this->plugin->setResponse($this->keygen());
	}

	public function keygen()
	{
		// Init variable
		$obj = new stdclass;
		$umodel = new JUser;
		$user = $umodel->getInstance();

		if( !$user->id )
		{
			$user = JFactory::getUser($this->plugin->get('user')->id);
			$app = JFactory::getApplication();
			$username = $app->input->get('username', 0, 'STRING');

			$model = FD::model('Users');
			$id = $model->getUserId('username', $username);
		}

		$kmodel = new ApiModelKey;
		$model = new ApiModelKeys;
		$key = null;

		// Get login user hash
		// $kmodel->setState('user_id', $user->id);
		$kmodel->setState('user_id', $id);
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
				// 'userid' => $user->id,
				'userid' => $id,
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

				// Add new key in easysocial table
				$easyblog = JPATH_ROOT . '/administrator/components/com_easyblog/easyblog.php';
				if (JFile::exists($easyblog) && JComponentHelper::isEnabled('com_easysocial', true))
				{
					$this->updateEauth($user, $key);
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
			$obj->message = JText::_('PLG_API_USERS_BAD_REQUEST_MESSAGE');
		}

		return( $obj );
	}

	/*
	 * function to update Easyblog auth keys
	 */
	public function updateEauth($user=null,$key=null)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
		$model 	= FD::model('Users');
		$id 	= $model->getUserId('username', $user->username);
		$user 	= FD::user($id);
		$user->alias = $user->username;
		$user->auth = $key;
		$user->store();

		return $id;
	}
}
