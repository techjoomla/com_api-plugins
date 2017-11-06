<?php
/**
 * @version    SVN: <svn_id>
 * @package    Users
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// No direct access.
defined('_JEXEC') or die;

require JPATH_SITE . '/plugins/api/users/users/userService.php';

/**
 * User Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.0
 */

class UsersApiResourceUser extends ApiResource
{
	/**
	 * Function post for create user record.
	 *
	 * @return void
	 */
	public function post()
	{
		// Get request body
		$requestBody = file_get_contents('php://input');

		$app = JFactory::getApplication();
		$app->set('reqBody', $requestBody);

		$model = JModelLegacy::getInstance('UsersSearch', 'UsersModel');

		$data = $model->getItems();

		$this->plugin->setResponse($data);

		return;
	}
}
