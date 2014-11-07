<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');
jimport('joomla.application.component.helper');

JModelLegacy::addIncludePath(JPATH_SITE.'components/com_api/models');
require_once JPATH_SITE.'/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE.'/components/com_api/libraries/authentication/login.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';

require_once JPATH_SITE.'/components/com_api/models/key.php';
require_once JPATH_SITE.'/components/com_api/models/keys.php';

class UsersApiResourceLogin extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse("unsupported method,please use post method");
	}

	public function post()
	{
	   $this->plugin->setResponse($this->keygen());
	}

	function keygen()
	{
		//init variable
		$obj = new stdclass;
		$umodel = new JUser;
		$user = $umodel->getInstance();

		if(!$user->id)
		{
			$user = JFactory::getUser($this->plugin->get('user')->id);
		}

		$kmodel = new ApiModelKey;
		$model = new ApiModelKeys;
		$key = null;
		//get login user hash
		$kmodel->setState('user_id',$user->id);
		$log_hash = $kmodel->getList();
		$log_hash = $log_hash[count($log_hash) - count($log_hash)];

		if($log_hash->hash)
		 {
			$key = $log_hash->hash;
		 }		
		else if($key==null || empty($key))
		{
			//create new key for user
			$data = array(
			'userid' =>$user->id,
			'domain' =>'' ,
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
			if(JComponentHelper::isEnabled('com_easysocial', true))
			{
				$this->updateEauth($user,$key);
			}
			
		}

		if(!empty($key))
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
	
	public function updateEauth($user=null,$key=null)
	{
		$model 	= FD::model('Users');
		$id 	= $model->getUserId('username', $user->username);
		$user 	= FD::user($id);
		$user->alias = $user->username;
		$user->auth = $key;
		$user->store();
		
		return $id;
		
	}	
	
	

}
