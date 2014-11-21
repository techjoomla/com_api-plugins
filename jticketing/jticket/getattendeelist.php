<?php
/**
 * @package API_plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class JticketApiResourcegetAttendeelist extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getattendee());
	}
	
	public function post()
	{
		$this->plugin->setResponse($this->getattendee());
	}
	
	public function getattendee()
	{
		$lang =JFactory::getLanguage();
		$extension = 'com_jticketing';
		$base_dir = JPATH_SITE;
		$jinput = JFactory::getApplication()->input;

		$lang->load($extension, $base_dir);

		$eventid = $jinput->get('eventid',0,'INT');
		$var['attendtype'] = $jinput->get('attendtype','all','CMD');
		$var['tickettypeid'] = $jinput->get('tickettypeid','0','INT');
		
		$jticketingmainhelper = new jticketingmainhelper();
		if(empty($eventid) OR empty($var))
			{
				$obj->success = 0;
				$obj->message =JText::_("COM_JTICKETING_INVALID_EVENT");
				return $obj;
			}
		$results=$jticketingmainhelper->GetOrderitemsAPI($eventid,$var);
		if(empty($results))
		{
			$obj->success = "0";
			$obj->data = JText::_("COM_JTICKETING_INVALID_TICKET");;
			return $obj;
		}

        if($eventid)
		{
			$data = array();
			foreach($results as &$orderitem)
			{
				$obj = new stdClass();
				if($orderitem->attendee_id)
				{
					$field_array=array('first_name','last_name');
					//Get Attendee Details
					$attendee_details=$jticketingmainhelper->getAttendees_details($orderitem->attendee_id,$field_array);
				}
				if(!empty($attendee_details['first_name']))
				{
					$buyername=implode(" ",$attendee_details);
				}
				else
				{
					$buyername=$orderitem->name;
				}

				$obj->checkin = $jticketingmainhelper->GetCheckinStatusAPI($orderitem->order_items_id);
				$obj->ticketid = $orderitem->oid.'-'.$orderitem->order_items_id;

				$obj->attendee_nm =strtolower($buyername);
				$obj->tickettypeid=$orderitem->tickettypeid;
				$obj->ticket_type_title =$orderitem->ticket_type_title;
				$obj->event_title = $orderitem->event_title;
				$obj->ticket_prefix=JText::_("TICKET_PREFIX");

				/*if($var['attendtype']=="all")
				{
					$data[] = $obj;
				}
				else if($var['attendtype']=="attended" && $obj->checkin==1)
				{
					$data[] = $obj;
				}
				else if($var['attendtype']=="notattended" && $obj->checkin==0)
				{
					$data[] = $obj;
				}*/
				$data[] = $obj;
			}

		    $fobj = new stdClass();
			$fobj->success = "1";
            $fobj->total = count($data);
			$fobj->data = $data;
        }
		else
		{
			$fobj->success = "0";
			$fobj->data = JText::_("COM_JTICKETING_INVALID_TICKET");
		}

		return $fobj;
	}

	public function put()
	{
		$obj = new stdClass();
		$obj->success = 0;
		$obj->code = 403;
		$obj->message = JText::_("COM_JTICKETING_WRONG_METHOD_PUT");
		$this->plugin->setResponse($obj);
	}
	public function delete()
	{
		$obj = new stdClass();
		$obj->success = 0;
		$obj->code = 403;
		$obj->message = JText::_("COM_JTICKETING_WRONG_METHOD_DEL");
		$this->plugin->setResponse($obj);
	}

}
