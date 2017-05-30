<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
 */

defined('_JEXEC') or die( 'Restricted access' );

JModelLegacy::addIncludePath(JPATH_SITE . 'components/com_api/models');
require_once JPATH_SITE . '/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE . '/components/com_api/libraries/authentication/login.php';
require_once JPATH_SITE . '/components/com_api/models/key.php';
require_once JPATH_SITE . '/components/com_api/models/keys.php';

class UsersApiResourceLogin extends ApiResource
{
	public function get()
	{
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_USERS_GET_METHOD_NOT_ALLOWED_MESSAGE');
		$this->plugin->setApiResponse( true, $result = null );
	}

	public function post()
	{
		$this->plugin->setApiResponse(false, $this->keygen());
	}

	public function keygen()
	{
		//init variable
		$obj = new stdclass;
		$umodel = new JUser;
		$user = $umodel->getInstance();       

		$app = JFactory::getApplication();
		$username = $app->input->get('username', 0, 'STRING');

		$user = JFactory::getUser();
		$id = JUserHelper::getUserId($username);

		if($id == null)
		{
			$model = FD::model('Users');
			$id = $model->getUserId('email', $username);            
		}

		$result = new stdClass;

		$kmodel = new ApiModelKey;
		$model = new ApiModelKeys;
		$key = null;
		// Get login user hash
		//$kmodel->setState('user_id', $user->id);
		$kmodel->setState('user_id', $id);
		$log_hash = $kmodel->getList();
		$log_hash = (!empty($log_hash))?$log_hash[count($log_hash) - count($log_hash)]:$log_hash;

		if( !empty($log_hash) )
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
			$result->result->token = $key;
			$result->result->id = $id;
		}
		else
		{
			$this->plugin->err_code		=	403;
			$this->plugin->err_message		=	JText::_('PLG_API_USERS_BAD_REQUEST_MESSAGE');
		}
		
		return( $result );
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
}
