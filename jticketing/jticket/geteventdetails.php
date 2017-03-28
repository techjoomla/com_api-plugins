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
 * Class for getting ticket list which are chekin or not checkin
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class JticketApiResourceGeteventdetails extends ApiResource
{
	/**
	 * Get Event details based on event id
	 *
	 * @return  json event details
	 *
	 * @since   1.0
	 */
	public function get()
	{
		$com_params           = JComponentHelper::getParams('com_jticketing');
		$integration          = $com_params->get('integration');
		$lang                 = JFactory::getLanguage();
		$extension            = 'com_jticketing';
		$base_dir             = JPATH_SITE;
		$jticketingmainhelper = new jticketingmainhelper;
		$lang->load($extension, $base_dir);
		$input   = JFactory::getApplication()->input;
		$eventid = $input->get('eventid', '0', 'INT');
		$userid  = $input->get('userid', '', 'INT');

		if (empty($eventid))
		{
			$obj->success = 0;
			$obj->message = JText::_("COM_JTICKETING_INVALID_EVENT");
			$this->plugin->setResponse($obj);

			return;
		}

		$eventdatapaid = $jticketingmainhelper->GetUserEventsAPI('', $eventid);
		$eveidarr      = array();

		if ($eventdatapaid)
		{
			foreach ($eventdatapaid as &$eventdata)
			{
				$eveidarr[]              = $eventdata->id;
				$eventdata->totaltickets = $jticketingmainhelper->GetTicketcount($eventdata->id);
			}
		}

		$eventdataunpaid = $jticketingmainhelper->GetUser_unpaidEventsAPI($eventid, $userid, $eveidarr);

		if ($eventdataunpaid)
		{
			foreach ($eventdataunpaid as &$eventdata)
			{
				$eventdata->totaltickets = $jticketingmainhelper->GetTicketcount($eventdata->id);
				$eventdata->soldtickets  = 0;
				$eventdata->checkin      = 0;
				$eventdata->availabletickets = 0;
			}
		}

		if ($eventdatapaid and $eventdataunpaid)
		{
			$obj_merged = array_merge((array) $eventdatapaid, (array) $eventdataunpaid);
		}
		elseif ($eventdatapaid and empty($eventdataunpaid))
		{
			$obj_merged = (array) $eventdatapaid;
		}
		elseif ($eventdataunpaid and empty($eventdatapaid))
		{
			$obj_merged = (array) $eventdataunpaid;
		}

		$obj = new stdClass;

		if ($obj_merged)
		{
			$config                   = JFactory::getConfig();
			$return                   = $jticketingmainhelper->getTimezoneString($eventdata->id);
			$sdate = date_create($return['startdate']);
			$obj_merged[0]->startdate = date_format($sdate, 'l, jS F Y');
			$edate = date_create($return['enddate']);
			$obj_merged[0]->enddate   = date_format($edate, 'l, jS F Y');
			$datetoshow               = $return['startdate'] . '-' . $return['enddate'];

			if (!empty($return['eventshowtimezone']))
			{
				$datetoshow .= '<br/>' . $return['eventshowtimezone'];
			}

			if (isset($obj_merged[0]->avatar))
			{
				if ($integration == 2)
				{
					$obj_merged[0]->avatar = JUri::base() . 'media/com_jticketing/images/' . $obj_merged[0]->avatar;
				}
				else
				{
					$obj_merged[0]->avatar = JUri::base() . $obj_merged[0]->avatar;
				}
			}
			else
			{
				$obj_merged[0]->avatar = '';
			}

			if (empty($obj_merged[0]->soldtickets))
			{
				$obj_merged[0]->soldtickets = 0;
			}

			if (empty($obj_merged[0]->totaltickets))
			{
				$obj_merged[0]->totaltickets = 0;
			}

			if (empty($obj_merged[0]->checkin))
			{
				$obj_merged[0]->checkin = 0;
			}
			if (empty($obj_merged[0]->availabletickets))
			{
				$obj_merged[0]->availabletickets = 0;
			}

			$obj->success = "1";
			$obj->data    = $obj_merged;
		}
		else
		{
			$obj->success = "0";
			$obj->data    = JText::_("COM_JTICKETING_NO_EVENT_DATA");
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
