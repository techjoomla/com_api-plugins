<?php
/**
 * @version    SVN: <svn_id>
 * @package    JTicketing
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\Session\Session;

BaseDatabaseModel::addIncludePath(JPATH_SITE . DS . 'components' . DS . 'com_api' . DS . 'models');
require_once JPATH_SITE . DS . 'components' . DS . 'com_api' . DS . 'libraries' . DS . 'authentication' . DS . 'login.php';
require_once JPATH_SITE . DS . 'components' . DS . 'com_api' . DS . 'models' . DS . 'key.php';
require_once JPATH_SITE . DS . 'components' . DS . 'com_api' . DS . 'models' . DS . 'keys.php';

/**
 * Class for login API
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class JticketApiResourceLogin extends ApiResource
{
	/**
	 * Get Event data
	 *
	 * @return  json user list
	 *
	 * @since   1.0
	 */
	public function get()
	{
		$lang      = Factory::getLanguage();
		$extension = 'com_jticketing';
		$base_dir  = JPATH_SITE;
		$lang->load($extension, $base_dir);
		$result  = $this->keygen();
		$success = $result->success;

		if (empty($success))
		{
			$result = Text::_("COM_JTICKETING_INVALID_USER_PASSWORD");
		}

		unset($result->success);

		if ($success)
		{
			$data1 = array(
				"data" => $result,
				"success" => $success
			);
		}
		else
		{
			$data1 = array(
				"message" => $result,
				"success" => $success
			);
		}

		$this->plugin->setResponse($data1);
	}

	/**
	 * Post Event data
	 *
	 * @return  json user list
	 *
	 * @since   1.0
	 */
	public function post()
	{
		$this->plugin->setResponse($this->keygen());
	}

	/**
	 * keygen
	 *
	 * @return  json user list
	 *
	 * @since   1.0
	 */
	public function keygen()
	{
		$umodel = new User;
		$user   = $umodel->getInstance();
		$group  = Factory::getApplication()->input->get('group');

		if (!$user->id)
		{
			$user = Factory::getUser($this->getUserId(Factory::getApplication()->input->get("username")));
		}

		$kmodel    = new ApiModelKey;
		$model     = new ApiModelKeys;
		$key       = null;
		$keys_data = $model->getList();

		foreach ($keys_data as $val)
		{
			if (!empty($user->id) and $val->user_id == $user->id)
			{
				$key = $val->hash;
			}
		}

		// Create new key for user
		if (!empty($user->id) and $key == null)
		{
			$data   = array(
				'user_id' => $user->id,
				'domain' => '',
				'published' => 1,
				'id' => '',
				'task' => 'save',
				'c' => 'key',
				'ret' => 'index.php?option=com_api&view=keys',
				'option' => 'com_api',
				Session::getFormToken() => 1
			);
			$result = $kmodel->save($data);
			$key    = $result->hash;
			$userid = $result->user_id;
		}

		$obj = new stdclass;

		if (!empty($user->id))
		{
			$obj->success = 1;
			$obj->userid  = $user->id;
			$obj->key     = $key;
			$obj->url     = Uri::base() . 'index.php';
		}
		else
		{
			$obj->success = 0;
			$obj->data    = Text::_("COM_JTICKETING_INVALID_USER_PASSWORD");
		}

		return ($obj);
	}

	/**
	 * function to get user name
	 *
	 * @param   object  $username  name of user
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function getUserId($username)
	{
		$db    = Factory::getDbo();
		$query = "SELECT u.id FROM #__users AS u WHERE u.username = '{$username}'";
		$db->setQuery($query);

		return $id = $db->loadResult();
	}
}
