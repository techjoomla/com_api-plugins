<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgAPIJticket extends ApiPlugin
{
	public function __construct()
	{
		parent::__construct();
		// Load all required helpers.
		$component_path=JPATH_ROOT.DS.'components'.DS.'com_jticketing';
		if(!file_exists($component_path))
		{
			return;
		}

		$jticketingmainhelperPath = JPATH_ROOT.DS.'components'.DS.'com_jticketing'.DS.'helpers'.DS.'main.php';

		if (!class_exists('jticketingmainhelper'))
		{
			JLoader::register('jticketingmainhelper', $jticketingmainhelperPath );
			JLoader::load('jticketingmainhelper');
		}

		$jticketingfrontendhelper = JPATH_ROOT.DS.'components'.DS.'com_jticketing'.DS.'helpers'.DS.'frontendhelper.php';

		if (!class_exists('jticketingfrontendhelper'))
		{
			JLoader::register('jticketingfrontendhelper', $jticketingfrontendhelper );
			JLoader::load('jticketingfrontendhelper');
		}

		$jteventHelperPath = JPATH_ROOT.DS.'components'.DS.'com_jticketing'.DS.'helpers'.DS.'event.php';

		if (!class_exists('jteventHelper'))
		{
			JLoader::register('jteventHelper', $jteventHelperPath );
			JLoader::load('jteventHelper');
		}

		ApiResource::addIncludePath(dirname(__FILE__).'/jticket');
	}
}
