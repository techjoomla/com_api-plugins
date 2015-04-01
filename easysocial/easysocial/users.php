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

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/comments.php';
/**
 * Comment Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.0
 */
class EasysocialApiResourceComment extends ApiResource
{
	/**
	 * Function delete for user record.
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->plugin->setResponse('in delete');
	}

	/**
	 * Function post for create user record.
	 *
	 * @return void
	 */
	public function post()
	{
		$this->plugin->setResponse('in post');
	}

	/**
	 * Function get for users record.
	 *
	 * @return void
	 */
	public function get()
	{
		$cmt_obj = new EasySocialModelComments();
		
	}
}
