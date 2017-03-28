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
class JticketApiResourceGetticketlist extends ApiResource
{
	/**
	 * Get Ticket list
	 *
	 * @return  json ticket list list
	 *
	 * @since   1.0
	 */
	public function get()
	{
		$lang      = JFactory::getLanguage();
		$extension = 'com_jticketing';
		$base_dir  = JPATH_SITE;
		$input     = JFactory::getApplication()->input;
		$lang->load($extension, $base_dir);
		$eventid              = $input->get('eventid', '0', 'INT');
		$var['attendtype']    = $input->get('attendtype', 'all', 'STRING');
		$var['tickettypeid']  = $input->get('tickettypeid', '0', 'INT');
		$search = $input->get('search', '', 'STRING');
		$jticketingmainhelper = new jticketingmainhelper;
		$results              = $jticketingmainhelper->GetOrderitemsAPI($eventid, $var);

		if (empty($results))
		{
			$obj          = new stdClass;
						
			$obj->success = "0";
			$obj->message = JText::_("COM_JTICKETING_INVALID_EVENT");
			return $this->plugin->setResponse($obj);

			//return;
		}

		if ($eventid)
		{
			$data = array();

			foreach ($results as &$orderitem)
			{
				$obj          = new stdClass;
				$obj->checkin = $jticketingmainhelper->GetCheckinStatusAPI($orderitem->order_items_id, $eventid);

				if ($orderitem->attendee_id)
				{
					$field_array      = array(
						'first_name',
						'last_name',
						'phone',
						'email'
					);

					// Get Attendee Details
					$attendee_details = $jticketingmainhelper->getAttendees_details($orderitem->attendee_id, $field_array,$search);
				}

				if (!empty($attendee_details['first_name']))
				{
					$attendee_nm = $attendee_details['first_name']." ".$attendee_details['last_name'];
				}						
				else
				{
					$attendee_nm = $orderitem->name;
				}
				if (!empty($attendee_details['phone']))
				{
					$attendee_phone = $attendee_details['phone'];		
				}
				if (!empty($attendee_details['email']))
				{
					$attendee_email = $attendee_details['email'];		
				}

				$obj->ticketid          = $orderitem->oid . '-' . $orderitem->order_items_id;
				$obj->attendee_nm       = $attendee_nm;
				$obj->tickettypeid      = $orderitem->tickettypeid;
				$obj->ticket_type_title = $orderitem->ticket_type_title;
				$obj->event_title       = $orderitem->event_title;
				$obj->ticket_prefix     = JText::_("TICKET_PREFIX");
				$obj->bought_on       	= $orderitem->cdate;
				$obj->price_per_ticket  = $orderitem->amount;
				$obj->original_amount   = $orderitem->totalamount;
				$obj->email   			= $attendee_email;
				$obj->phone   			= $attendee_phone;
								
				if ($var['attendtype'] == "all")
				{
					$data[] = $obj;
				}

				elseif ($var['attendtype'] == "attended" && $obj->checkin == 1)
				{
					$data[] = $obj;
				}
				elseif ($var['attendtype'] == "notattended" && $obj->checkin == 0)
				{
					$data[] = $obj;
				}
			}

			$fobj          = new stdClass;
			$fobj->success = "1";
			$fobj->total   = count($data);
			$fobj->data    = $data;
		}
		else
		{
			$fobj          = new stdClass; 
			
			$fobj->success = "0";
			$fobj->message = JText::_("COM_JTICKETING_INVALID_EVENT");
		}

		$this->plugin->setResponse($fobj);
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
