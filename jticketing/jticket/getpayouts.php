<?php
/**
 * @version    SVN: <svn_id>
 * @package    JTicketing
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');
require_once JPATH_ADMINISTRATOR.'/components/com_jticketing/models/mypayouts.php';

/**
 * Class for getting user events based on user id
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class JticketApiResourceGetPayouts extends ApiResource
{
	/**
	 * Get Event data
	 *
	 * @return  json user list
	 *
	 * @since   1.0
	 */
	public function get()
	{
		
		$com_params  = JComponentHelper::getParams('com_jticketing');
		$integration = $com_params->get('integration');
		$input       = JFactory::getApplication()->input;
		$lang      = JFactory::getLanguage();
		$extension = 'com_jticketing';
		$base_dir  = JPATH_SITE;
		$lang->load($extension, $base_dir);
		$obj_merged = array();

		$userid = $input->get('userid', '', 'INT');
		$search = $input->get('search', '', 'STRING');
		
		if (empty($userid))
		{
			$obj->success = 0;
			$obj->message = JText::_("COM_JTICKETING_INVALID_USER");
			$this->plugin->setResponse($obj);

			return;
		}

		$jticketingmainhelper = new jticketingmainhelper;
		$plugin = JPluginHelper::getPlugin('api', 'jticket');

		// Check if plugin is enabled
		if ($plugin)
		{
			// Get plugin params
			$pluginParams = new JRegistry($plugin->params);
			$users_allow_access_app = $pluginParams->get('users_allow_access_app');
		}

		// If user is in allowed user to access APP show all events to that user
		if (is_array($users_allow_access_app) and in_array($userid, $users_allow_access_app))
		{
			$eventdatapaid        = $jticketingmainhelper->getMypayoutData();
		}
		else
		{	
			$eventdatapaid        = $jticketingmainhelper->getMypayoutData($userid,$search);																				
		}
				
		$db = JFactory::getDBO();
		$db->setQuery($eventdatapaid);
		$obj_merged = $db->loadObjectlist();	

		$obj = new stdClass;

		if ($obj_merged)
		{
			$obj = $obj_merged;
		}
		else
		{
			$obj->success = "0";
			$obj->message = JText::_("COM_JTICKETING_NO_EVENT_DATA_USER");
		}

		$this->plugin->setResponse($obj);
	}

	/**
	 * Post Method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function post()
	{
		$obj          = new stdClass;
		$obj->success = 0;
		$obj->code    = 20;
		$obj->message = JText::_("COM_JTICKETING_SELECT_GET_METHOD");
		$this->plugin->setResponse($obj);
	}

	/**
	 * Put method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function put()
	{
		$obj          = new stdClass;
		$obj->success = 0;
		$obj->code    = 20;
		$obj->message = JText::_("COM_JTICKETING_SELECT_GET_METHOD");
		$this->plugin->setResponse($obj);
	}

	/**
	 * Delete method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function delete()
	{
		$obj          = new stdClass;
		$obj->success = 0;
		$obj->code    = 20;
		$obj->message = JText::_("COM_JTICKETING_SELECT_GET_METHOD");
		$this->plugin->setResponse($obj);
	}
}
