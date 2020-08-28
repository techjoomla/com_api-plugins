<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Menu
 *
 * @copyright   Copyright (C) 2016 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

/**
 * Importer Plugin.
 *
 * @since  3.0
 */
class PlgAPIMenu extends ApiPlugin
{
	/**
	 * Constructor
	 *
	 * @param   string  &$subject  The name of the Plugin group.
	 * @param   array   $config    Config params array.
	 *
	 * @since  3.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject , $config = array());

		ApiResource::addIncludePath(dirname(__FILE__) . '/menu');

		$this->setResourceAccess('menus', 'public', 'get');
		$this->setResourceAccess('showmenus', 'public', 'get');

	}
}
