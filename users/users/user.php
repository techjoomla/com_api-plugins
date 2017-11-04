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
