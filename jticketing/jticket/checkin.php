<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');


class JticketApiResourceCheckin extends ApiResource
{
	public function get()
	{
		jimport( 'joomla.application.component.helper' );
		$send_email_after_checkin=0;
		$com_params=JComponentHelper::getParams('com_jticketing');
		$send_email_after_checkin = $com_params->get('send_email_after_checkin');
		$jticketingmainhelper = new jticketingmainhelper();
		$lang =JFactory::getLanguage();
		$extension = 'com_jticketing';
		$base_dir = JPATH_SITE;
		$lang->load($extension, $base_dir);
		$ticketidstr = JRequest::getVar('ticketid',0);
		$eventid = JRequest::getVar('eventid',0);
		$eventintegrationid=$jticketingmainhelper->getEventrefid($eventid);

        if(empty($ticketidstr))
        {
			$obj->success = 0;
			$obj->message =JText::_("COM_JTICKETING_INVALID_TICKET");
			$this->plugin->setResponse($obj);
			return;


		}

		$ticketidarr=explode('-',$ticketidstr);
		$oid=$ticketidarr[0];
		$orderitemsid=$ticketidarr[1];
		$obj = new stdClass();

		$result=$jticketingmainhelper->GetActualTicketidAPI($oid,$orderitemsid,'');
		$originalticketid=JText::_("TICKET_PREFIX").$ticketidstr;

		if($result[0]->attendee_id)
		{
			$field_array=array('first_name','last_name');
			//Get Attendee Details
			$attendee_details=$jticketingmainhelper->getAttendees_details($result[0]->attendee_id,$field_array);
		}

		if(!empty($attendee_details['first_name']))
		{
			$attendernm=implode(" ",$attendee_details);
		}
		else
		{
			$attendernm=$result[0]->name;
		}

		if(empty($result))
		{
			$obj->success = 0;
			$obj->message =JText::_("COM_JTICKETING_INVALID_TICKET");

		}
		else if($result[0]->eventid!=$eventintegrationid)
		{
			$obj->success = 0;
			$obj->message =JText::_("COM_JTICKETING_INVALID_TICKET");
		}
		else
		{
			$checkindone=$jticketingmainhelper->GetCheckinStatusAPI($orderitemsid);
			if($checkindone)
			{
				 $obj->success = 0;
				 if($attendernm)
				 $obj->message =sprintf(JText::_('COM_JTICKETING_CHECKIN_FAIL_DUPLICATE'),$attendernm);
				 else
				 $obj->message =JText::_('COM_JTICKETING_CHECKIN_FAIL_DUPLICATE');

			}
			else
			{


				$obj->success = $jticketingmainhelper->DoCheckinAPI($orderitemsid,$result);

				if($obj->success)
				{
					$obj->success = 1;
					$ticketTypeData=$jticketingmainhelper->GetTicketTypes('',$result[0]->type_id);
					$app =JFactory::getApplication();
					$mailfrom = $app->getCfg('mailfrom');
					$fromname = $app->getCfg('fromname');
					$sitename = $app->getCfg('sitename');
					$message=JText::_("CHECKIN_MESSAGE");
					$message_subject=JText::_("CHECKIN_SUBJECT");
					$eventnm=$jticketingmainhelper->getEventTitle($oid);
					$message_subject= stripslashes($message_subject);
					$message_subject	= str_replace("[EVENTNAME]", $eventnm,$message_subject);

					$message_body	= stripslashes($message);
					$message_body	= str_replace("[EVENTNAME]", $eventnm,$message_body);
					$message_body	= str_replace("[NAME]", $attendernm,$message_body);
					$message_body	= str_replace("[TICKETID]", $originalticketid,$message_body);
					$message_body	= str_replace("[TICKET_TYPE_TITLE]", $ticketTypeData[0]->title,$message_body);


					if($send_email_after_checkin)
					{
						$result=JFactory::getMailer()->sendMail($fromname,$mailfrom,$result[0]->email,$message_subject,$message_body,$html=1,null,null,'');
					}
				}

				if($attendernm and $ticketTypeData[0]->title and $originalticketid)
				{
					$obj->message =sprintf(JText::_('COM_JTICKETING_CHECKIN_SUCCESS'),$attendernm,$ticketTypeData[0]->title,$originalticketid);
				}
				else
				{
					$obj->message =JText::_('COM_JTICKETING_CHECKIN_SUCCESS');

				}
			}
		}

		$this->plugin->setResponse($obj);
	}

	/**
	 * This is not the best example to follow
	 * Please see the category plugin for a better example
	 */
	public function post()
	{


	}

	public function put()
	{

	}


}
