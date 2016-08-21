<?php
/**
 * @version    SVN: <svn_id>
 * @package    JTicketing
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die( 'Restricted access');
jimport('joomla.plugin.plugin');

/**
 * Base Class for api plugin
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class PlgAPIJticket extends ApiPlugin
{
	/**
	 * Jticketing api plugin to load com_api classes
	 *
	 * @param   string  $subject  originalamount
	 * @param   array   $config   coupon_code
	 *
	 * @since   1.0
	 */
	public function __construct($subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		// Load all required helpers.
		$component_path = JPATH_ROOT . '/components/com_jticketing';

		if (!file_exists($component_path))
		{
			return;
		}

		$jticketingmainhelperPath = JPATH_ROOT . '/components/com_jticketing/helpers/main.php';

		if (!class_exists('jticketingmainhelper'))
		{
			JLoader::register('jticketingmainhelper', $jticketingmainhelperPath);
			JLoader::load('jticketingmainhelper');
		}

		$jticketingfrontendhelper = JPATH_ROOT . '/components/com_jticketing/helpers/frontendhelper.php';

		if (!class_exists('jticketingfrontendhelper'))
		{
			JLoader::register('jticketingfrontendhelper', $jticketingfrontendhelper);
			JLoader::load('jticketingfrontendhelper');
		}

		$jteventHelperPath = JPATH_ROOT . '/components/com_jticketing/helpers/event.php';

		if (!class_exists('jteventHelper'))
		{
			JLoader::register('jteventHelper', $jteventHelperPath);
			JLoader::load('jteventHelper');
		}

		ApiResource::addIncludePath(dirname(__FILE__) . '/jticket');
	}
}
