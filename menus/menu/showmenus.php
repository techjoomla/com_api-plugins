<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Menu
 *
 * @copyright   Copyright (C) 2016 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');
jimport('joomla.registry.registry');
JLoader::register('ModMenuHelper', JPATH_SITE . '/modules/mod_menu/helper.php');

use Joomla\CMS\Factory;

/**
 * Showmenus Resource for Joomla Menu Plugin.
 *
 * @since  2.5
 */
class MenuApiResourceShowmenus extends ApiResource
{
	/**
	 * GET function fetch batches or batch based on passed param
	 *
	 * ***INPUT PARAMS***
	 * *menutype			- type of menu to get filtered items (not mandatory)
	 *
	 * @return  JSON  batch details
	 *
	 * @since  3.0
	 **/
	public function get()
	{
		$app		= JFactory::getApplication();
		$jinput		= $app->input;
		$menuType	= $jinput->get('menutype', '', 'STRING');

		if (!$menuType)
		{
			ApiError::raiseError(400, JText::_('MenuType required'));
		}

		$data = new stdClass;

		$data->menutype = $menuType;
		$data->base = '';
		$data->startLevel = 1;
		$data->endLevel = 0;
		$data->showAllChildren = 0;
		$data->cache = 0;
		$data->cache_time = 0;
		$data->cachemode = '';

		$params	= new JRegistry($data);
		$list	= ModMenuHelper::getList($params);

		$uriBaseArr		= explode("/", JUri::base());
		$uriBaseLast	= $uriBaseArr[count($uriBaseArr) - 2];

		foreach ($list as &$listEle)
		{
			$listElemLinkArr = explode("/", $listEle->flink);

			if ($listElemLinkArr[1] === $uriBaseLast)
			{
				$listElemLinkArr = array_slice($listElemLinkArr, 2);
			}
			else
			{
				$listElemLinkArr = array_filter($listElemLinkArr);
			}

			$listEle->flink = implode("/", $listElemLinkArr);
		}

		$this->plugin->setResponse($list);
	}
}
