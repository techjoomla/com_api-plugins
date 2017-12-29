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
        $app	= JFactory::getApplication();
        $menu	= $app->getMenu();
        $jinput	= $app->input;
        
        $filterKeys = array();
        $filterVals = array();
        
        if ($menuType = $jinput->get('menutype', '', 'STRING'))
        {
            array_push($filterKeys, 'menutype');
            array_push($filterVals, $menuType);
        }
        
        if ($component = $jinput->get('component', '', 'STRING'))
        {
            array_push($filterKeys, 'component');
            array_push($filterVals, $component);
        }
        
        if($level = $jinput->get('level', 1, 'INT'))
        {
            array_push($filterKeys, 'level');
            array_push($filterVals, $level);
        }
        
        $items	= $menu->getItems($filterKeys, $filterVals);
        
        $this->plugin->setResponse($items);
    }
}
