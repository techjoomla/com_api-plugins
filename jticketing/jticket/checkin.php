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
 * Class for checkin to tickets for mobile APP
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class JticketApiResourceCheckin extends ApiResource
{
	/**
	 * Checkin to tickets for mobile APP
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function get()
	{
		$this->plugin->setResponse('Get method not allow, Use post method.');
	}

	/**
	 * Checkin to tickets for mobile APP
	 *
	 * @return  json event details
	 *
	 * @since   1.0
	 */
	public function post()
	{
		jimport('joomla.application.component.helper');
		$com_params               = JComponentHelper::getParams('com_jticketing');
		$jticketingmainhelper     = new jticketingmainhelper;
		$lang                     = JFactory::getLanguage();
		$extension                = 'com_jticketing';
		$base_dir                 = JPATH_SITE;
		$lang->load($extension, $base_dir);
		$input              = JFactory::getApplication()->input;
		$ticketidstr_arr        = $input->get('ticketid', array(), 'ARRAY');
		$eventid            = $input->get('eventid', '0', 'INT');
		$eventintegrationid = $jticketingmainhelper->getEventrefid($eventid);
		$obj   = new stdClass;
		$result_arr = array();
		$attendee_details = array();

		foreach ($ticketidstr_arr as $ky => $ticketidstr)
		{
			if (empty($ticketidstr))
			{
				$obj->success = 0;
				$obj->message = JText::_("COM_JTICKETING_INVALID_TICKET");
				$this->plugin->setResponse($obj);

				return;
			}

			$ticketidarr      = explode('-', $ticketidstr);
			$oid              = $ticketidarr[0];
			$orderitemsid     = $ticketidarr[1];

			// Vishal -adjustment for random change in ticket id
			if (count($ticketidarr) > 2)
			{
				$oid              = $ticketidarr[1];
				$orderitemsid     = $ticketidarr[2];
			}

			$result           = $jticketingmainhelper->GetActualTicketidAPI($oid, $orderitemsid, '');
			$originalticketid = JText::_("TICKET_PREFIX") . $ticketidstr;

			if (empty($result))
			{
				$obj->ticket_id = $ticketidstr;
				$obj->success = 0;
				$obj->message = JText::_("COM_JTICKETING_INVALID_TICKET");
				$result_arr[$ky] = $obj;
				continue;
			}
			elseif ($result[0]->attendee_id)
			{
				$field_array = array('first_name', 'last_name');

				// Get Attendee Details
				$attendee_details = $jticketingmainhelper->getAttendees_details($result[0]->attendee_id, $field_array);
			}

			if (!empty($attendee_details['first_name']))
			{
				//$attendernm = implode(" ", $attendee_details);
				$attendernm = $attendee_details['first_name']." ".$attendee_details['last_name'];
			}
			else
			{
				$attendernm = $result[0]->name;
			}

			if (empty($result) || ($result[0]->eventid != $eventintegrationid))
			{
				$obj->success = 0;
				$obj->message = JText::_("COM_JTICKETING_INVALID_TICKET_OTHER_EVENT");
				$result_arr[$ky] = $obj;
				continue;
			}
			else
			{
				$result_arr[] = $this->checkTickets($orderitemsid, $attendernm, $originalticketid, $result, $oid, $eventid);
			}
		}

		$this->plugin->setResponse($result_arr);
	}

	/**
	 * Checkin to tickets for mobile APP
	 *
	 *
	 *
	 * @since   1.0
	 */

	/**
	 *  Checkin to tickets for mobile APP
	 *
	 * @param   integer  $orderitemsid      orderitemsid
	 * @param   string   $attendernm        attendernm
	 * @param   string   $originalticketid  originalticketid
	 * @param   string   $result            result
	 * @param   string   $oid               oid
	 * @param   integer  $eventid           eventid
	 *
	 * @return  json event details
	 *
	 * @since   1.0
	 */
	Public function checkTickets($orderitemsid, $attendernm, $originalticketid, $result, $oid, $eventid)
	{
		$jticketingmainhelper = new jticketingmainhelper;
		$obj  = new stdClass;
		$send_email_after_checkin = 0;
		$com_params               = JComponentHelper::getParams('com_jticketing');
		$send_email_after_checkin = $com_params->get('send_email_after_checkin');

		$checkindone = $jticketingmainhelper->GetCheckinStatusAPI($orderitemsid, $eventid);

			if ($checkindone)
			{
				$obj->success = 0;

				if ($attendernm)
				{
					$obj->message = sprintf(JText::_('COM_JTICKETING_CHECKIN_FAIL_DUPLICATE'), $attendernm);
				}
				else
				{
					$obj->message = JText::_('COM_JTICKETING_CHECKIN_FAIL_DUPLICATE');
				}
			}
			else
			{
				$items_checkin = array($orderitemsid);

				$JticketingModelattendee_List = JPATH_ROOT . '/components/com_jticketing/models/attendee_list.php';

				if (!class_exists('JticketingModelattendee_List'))
				{
					JLoader::register('JticketingModelattendee_List', $JticketingModelattendee_List);
					JLoader::load('JticketingModelattendee_List');
				}

				$JticketingModelattendee_List = new JticketingModelattendee_List;
				$obj->success = $JticketingModelattendee_List->setItemState_checkin($items_checkin, 1);

				if ($obj->success)
				{
					$obj->success    = 1;

					$ticketTypeData  = $jticketingmainhelper->GetTicketTypes('', $result[0]->type_id);
					$app             = JFactory::getApplication();
					$mailfrom        = $app->getCfg('mailfrom');
					$fromname        = $app->getCfg('fromname');
					$sitename        = $app->getCfg('sitename');
					$message         = JText::_("CHECKIN_MESSAGE");
					$message_subject = JText::_("CHECKIN_SUBJECT");
					$eventnm         = $jticketingmainhelper->getEventTitle($oid);
					$message_subject = stripslashes($message_subject);
					$message_subject = str_replace("[EVENTNAME]", $eventnm, $message_subject);
					$message_body    = stripslashes($message);
					$message_body    = str_replace("[EVENTNAME]", $eventnm, $message_body);
					$message_body    = str_replace("[NAME]", $attendernm, $message_body);
					$message_body    = str_replace("[TICKETID]", $originalticketid, $message_body);
					$message_body    = str_replace("[TICKET_TYPE_TITLE]", $ticketTypeData[0]->title, $message_body);

					if ($send_email_after_checkin)
					{
						$result = JFactory::getMailer()->sendMail($fromname, $mailfrom, $result[0]->email, $message_subject, $message_body, $html = 1, null, null, '');
					}
				}

				if ($attendernm and $ticketTypeData[0]->title and $originalticketid)
				{
					$obj->ticket_id = $originalticketid;
					$obj->message = sprintf(JText::_('COM_JTICKETING_CHECKIN_SUCCESS'), $attendernm, $ticketTypeData[0]->title, $originalticketid);
				}
				else
				{
					$obj->ticket_id = $originalticketid;
					$obj->message = JText::_('COM_JTICKETING_CHECKIN_SUCCESS');
				}
			}

			return $obj;
	}
}
