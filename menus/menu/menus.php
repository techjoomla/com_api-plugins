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
use Joomla\CMS\Factory;

/**
 * Menus Resource for Joomla Menu Plugin.
 *
 * @since  2.5
 */
class MenuApiResourceMenus extends ApiResource
{
	/**
	 * GET function fetch batches or batch based on passed param
	 *
	 * ***INPUT PARAMS***
	 * *menutype			- type of menu to get filtered items (not mandatory)
	 * *component			- assigned component of menu to get filtered items (not mandatory)
	 *
	 * @return  JSON  batch details
	 *
	 * @since  3.0
	 **/
	public function get()
	{
		$app			= Factory::getApplication();
		$this->menus	= $menu	= $app->getMenu();
		$jinput			= $app->input;
		$user			= Factory::getUser();
		$aliasMenuItems = array();

		$this->uriBase		= (String) JUri::root(true);

		$filterKeys = array();
		$filterVals = array();

		if ($menuType == $jinput->get('menutype', '', 'STRING'))
		{
			array_push($filterKeys, 'menutype');
			array_push($filterVals, $menuType);
		}

		if ($component == $jinput->get('component', '', 'STRING'))
		{
			array_push($filterKeys, 'component');
			array_push($filterVals, $component);
		}

		if ($level == $jinput->get('level', 1, 'INT'))
		{
			array_push($filterKeys, 'level');
			array_push($filterVals, $level);
		}

		if ($user->id)
		{
			array_push($filterKeys, 'access');
			array_push($filterVals, $user->getAuthorisedViewLevels());
		}

		$itemss	= (array) $menu->getItems($filterKeys, $filterVals, false);


		if($jinput->get('component', '', 'STRING') === 'com_content')
		{
			$aliasMenuItems	= $menu->getItems(array('type'), array('alias'), false);
			$items = array_merge($itemss, $aliasMenuItems);
		}
		else
		{
			$items = $itemss;
		}

		$this->getSubMenus($items);
		$this->setMenuConfigs($items);

		// if ($menuType == 'mainmenu')
		// {
		// 	$this->getSubMenus($items);
		// }

		$items = array_values($items);

		$this->plugin->setResponse($items);
	}

	/**
	 * Function to set sub-menu
	 *
	 * @param   ARRAY  &$items  Menu Items
	 *
	 * @return  JSON  batch details
	 *
	 * @since  3.0
	 **/
	private function getSubMenus(&$items)
	{
		$menu = Factory::getApplication()->getMenu();

		foreach ($items as $i => &$mn)
		{
			$mn->submenus = (array) $menu->getItems('parent_id', $mn->id);
		}
	}

	/**
	 * Function to set menu configs- copied from mod_menu
	 *
	 * @param   ARRAY  &$items  Menu Items
	 *
	 * @return  JSON  batch details
	 *
	 * @since  3.0
	 **/
	private function setMenuConfigs(&$items)
	{
		$start          = 1;
		$end            = 0;
		$showAll        = 0;
		$hidden_parents = array();
		$lastitem       = 0;

		if ($items)
		{
			// This loop has been taken from mod_menu helper file to do some Joomla Checks
			foreach ($items as $i => $item)
			{
				$item->parent = false;

				if (isset($items[$lastitem]) && $items[$lastitem]->id == $item->parent_id && $item->params->get('menu_show', 1) == 1)
				{
					$items[$lastitem]->parent = true;
				}

				//~ if (($start && $start > $item->level)
					//~ || ($end && $item->level > $end)
					//~ || (!$showAll && $item->level > 1 && !in_array($item->parent_id, $path))
					//~ || ($start > 1 && !in_array($item->tree[$start - 2], $path)))
				//~ {
					//~ unset($items[$i]);
					//~ continue;
				//~ }


				// Exclude item with menu item option set to exclude from menu modules
				if (($item->params->get('menu_show', 1) == 0) || in_array($item->parent_id, $hidden_parents))
				{
					$hidden_parents[] = $item->id;
					unset($items[$i]);
					continue;
				}

				$item->deeper     = false;
				$item->shallower  = false;
				$item->level_diff = 0;

				if (isset($items[$lastitem]))
				{
					$items[$lastitem]->deeper     = ($item->level > $items[$lastitem]->level);
					$items[$lastitem]->shallower  = ($item->level < $items[$lastitem]->level);
					$items[$lastitem]->level_diff = ($items[$lastitem]->level - $item->level);
				}

				$lastitem     = $i;
				$item->active = false;
				$item->flink  = $item->link;

				// Reverted back for CMS version 2.5.6
				switch ($item->type)
				{
					case 'separator':
						break;

					case 'heading':
						// No further action needed.
						break;

					case 'url':
						if ((strpos($item->link, 'index.php?') === 0) && (strpos($item->link, 'Itemid=') === false))
						{
							// If this is an internal Joomla link, ensure the Itemid is set.
							$item->flink = $item->link . '&Itemid=' . $item->id;
						}
						break;

					case 'alias':
						$item->flink = 'index.php?Itemid=' . $item->params->get('aliasoptions');

						$aliasMenuItem = $this->menus->getItem($item->params->get('aliasoptions'));

						if($aliasMenuItem && $aliasMenuItem->id)
						{
							$item->query = $aliasMenuItem->query;
						}

						break;

					default:
						$item->flink = 'index.php?Itemid=' . $item->id;
						break;
				}

				// We prevent the double encoding because for some reason the $item is shared for menu modules and we get double encoding
				// when the cause of that is found the argument should be removed
				$item->anchor_css     = $item->params->get('menu-anchor_css', '');
				$item->anchor_title   = $item->params->get('menu-anchor_title', '');
				$item->anchor_rel     = $item->params->get('menu-anchor_rel', '');
				$item->menu_image     = $item->params->get('menu_image', '') ? $item->params->get('menu_image', '') : '';
				$item->menu_image_css = $item->params->get('menu_image_css', '');

				$item->flink = $this->nonSefToSef($item->flink);
			}

			if (isset($items[$lastitem]))
			{
				$items[$lastitem]->deeper     = (($start ?: 1) > $items[$lastitem]->level);
				$items[$lastitem]->shallower  = (($start ?: 1) < $items[$lastitem]->level);
				$items[$lastitem]->level_diff = ($items[$lastitem]->level - ($start ?: 1));
			}
		}
	}

	/**
	 * Function to convert non-sef urls to sef
	 *
	 * @param   string  $nonSef  non-sef url
	 *
	 * @return  String  sef-url
	 *
	 * @since  3.0
	 **/
	private function nonSefToSef($nonSef)
	{
		$nonSefUrl 		= $nonSef;
		$sefUrl			= JRoute::_($nonSefUrl);
		$uriBase		= $this->uriBase;
		$replacements	= array();

		$uriBaseArr		= array_filter(explode('/', $uriBase));

		foreach ($uriBaseArr AS &$ele)
		{
			$ele = '/' . $ele . '/';
			$replacements[] = '';
		}

		if ($uriBase && (strpos($sefUrl, $uriBase) === 0))
		{
			$sefUrl = preg_replace($uriBaseArr, $replacements, $sefUrl, 1);
		}

		return trim($sefUrl, "/");
	}
}
