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

/**
 * Class for getting user events based on user id
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class JticketApiResourceGetuserevents extends ApiResource
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

		$search = $input->get('search', '', 'STRING');
		$userid = $input->get('userid', '', 'INT');

		$obj_merged = array();

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
			$eventdatapaid        = $jticketingmainhelper->GetUserEventsAPI();
		}
		else
		{
			$eventdatapaid        = $jticketingmainhelper->GetUserEventsAPI($userid, '', $search);
		}

		$eveidarr = array();

		if ($eventdatapaid)
		{
			foreach ($eventdatapaid as &$eventdata1)
			{
				$eveidarr[] = $eventdata1->id;

				if (isset($eventdata1->avatar))
				{
					$eventdata1->avatar = $eventdata1->avatar;
				}
				else
				{
					$eventdata1->avatar = '';
				}

				$eventdata1->totaltickets = $jticketingmainhelper->GetTicketcount($eventdata1->id);

				if (empty($eventdata1->totaltickets))
				{
					$eventdata1->totaltickets = 0;
				}

				$return                = $jticketingmainhelper->getTimezoneString($eventdata1->id);
				$eventdata1->startdate = $return['startdate'];
				$eventdata1->enddate   = $return['enddate'];
				$datetoshow            = $return['startdate'] . '-' . $return['enddate'];

				if (!empty($return['eventshowtimezone']))
				{
					$datetoshow .= $return['eventshowtimezone'];
				}
			}
		}

		$eventdataunpaid = $jticketingmainhelper->GetUser_unpaidEventsAPI('', $userid, $eveidarr, $search);

		if ($eventdataunpaid)
		{
			foreach ($eventdataunpaid as &$eventdata3)
			{
				$eventdata3->totaltickets = $jticketingmainhelper->GetTicketcount($eventdata3->id);

				if (empty($eventdata3->totaltickets))
				{
					$eventdata3->totaltickets = 0;
				}

				$eventdata3->startdate   = $jticketingmainhelper->getLocaletime($userid, $eventdata3->startdate);
				$eventdata3->enddate     = $jticketingmainhelper->getLocaletime($userid, $eventdata3->enddate);
				$eventdata3->soldtickets = 0;
				$eventdata3->checkin     = 0;
			}
		}

		if ($eventdatapaid and $eventdataunpaid)
		{
			$obj_merged = array_merge((array) $eventdatapaid, (array) $eventdataunpaid);
		}
		elseif ($eventdatapaid and empty($eventdataunpaid))
		{
			$obj_merged = $eventdatapaid;
		}
		elseif ($eventdataunpaid and empty($eventdatapaid))
		{
			$obj_merged = $eventdataunpaid;
		}

		$obj = new stdClass;

		if ($obj_merged)
		{
			foreach ($obj_merged as &$objmerged)
			{
				if (empty($objmerged->soldtickets))
				{
					$objmerged->soldtickets = 0;
				}

				if (empty($objmerged->totaltickets))
				{
					$objmerged->totaltickets = 0;
				}

				if (isset($objmerged->avatar))
				{
					if ($integration == 2)
					{
						$objmerged->avatar = JUri::base() . 'media/com_jticketing/images/' . $objmerged->avatar;
					}
					else
					{
						$objmerged->avatar = JUri::base() . $objmerged->avatar;
					}
				}
				else
				{
					$eventdata3->avatar = '';
				}

				if (empty($objmerged->checkin))
				{
					$objmerged->checkin = 0;
				}
			}

			$obj->success = "1";
			$obj->data    = $obj_merged;
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
