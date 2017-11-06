<?php
/**
 * @version    SVN: <svn_id>
 * @package    com_api.plugins
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

/**
 * Base Class for api plugin
 *
 * @package     com_api.plugins
 * @subpackage  plugins
 * @since       1.0
 */

class PlgAPIUsers extends ApiPlugin
{
	/**
	 * Users api plugin to load com_api classes
	 *
	 * @param   string  &$subject  originalamount
	 * @param   array   $config    coupon_code
	 *
	 * @since   1.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		ApiResource::addIncludePath(dirname(__FILE__) . '/users');

		/*load language file for plugin frontend*/
		$lang = JFactory::getLanguage();
		$lang->load('plg_api_users', JPATH_ADMINISTRATOR, '', true);

		// Set the login resource to be public
		$this->setResourceAccess('login', 'public', 'get');
		$this->setResourceAccess('users', 'public', 'post');
		$this->setResourceAccess('config', 'public', 'get');
	}
}
