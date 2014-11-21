<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');


class JticketApiResourceGetuserevents extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getUserEvent());
	}
	
	public function post()
	{
		$this->plugin->setResponse($this->getUserEvent());
	}
	
	public function getUserEvent()
	{
		$com_params=JComponentHelper::getParams('com_jticketing');
		//0 Jomsocial 1/Native 3 JEvents
		$integration = $com_params->get('integration');

		$lang =& JFactory::getLanguage();
		$extension = 'com_jticketing';
		$base_dir = JPATH_SITE;
		$jinput = JFactory::getApplication()->input;

		$lang->load($extension, $base_dir);
		$userid = $jinput->get('userid',0,'INT');
		
		if(empty($userid))
		{
			$obj->success = 0;
			$obj->message =JText::_("COM_JTICKETING_INVALID_USER");
			return $obj;
		}
		$jticketingmainhelper = new jticketingmainhelper();
		$eventdatapaid= $jticketingmainhelper->GetUserEventsAPI($userid,'');

		$eveidarr=array();
		if($eventdatapaid)
		{
			foreach($eventdatapaid as &$eventdata1)
			{
				$eveidarr[]=$eventdata1->id;
				if($eventdata1->avatar)
				$eventdata1->avatar=$eventdata1->avatar;
				else
				$eventdata1->avatar='';

				$eventdata1->totaltickets= $jticketingmainhelper->GetTicketcount($eventdata1->id);

				if(empty($eventdata1->totaltickets))
				$eventdata1->totaltickets=0;
				$return=$jticketingmainhelper->getTimezoneString($eventdata1->id);
				$eventdata1->startdate=$return['startdate'];
				$eventdata1->enddate=$return['enddate'];
				$datetoshow=$return['startdate'].'-'.$return['enddate'];
				if(!empty($return['eventshowtimezone']))
				$datetoshow.='<br/>'.$return['eventshowtimezone'];
			}
		}

		$eventdataunpaid= $jticketingmainhelper->GetUser_unpaidEventsAPI($userid,$eveidarr,'');
		if($eventdataunpaid)
		{
			foreach($eventdataunpaid as &$eventdata3)
			{
				$eventdata3->totaltickets= $jticketingmainhelper->GetTicketcount($eventdata3->id);
				if(empty($eventdata3->totaltickets))
				$eventdata3->totaltickets=0;
				$eventdata3->startdate= $jticketingmainhelper->getLocaletime($userid,$eventdata3->startdate);
				$eventdata3->enddate= $jticketingmainhelper->getLocaletime($userid,$eventdata3->enddate);
				$eventdata3->soldtickets=0;
				$eventdata3->checkin=0;
			}
		}
		if($eventdatapaid and $eventdataunpaid)
		$obj_merged =  array_merge((array) $eventdatapaid, (array) $eventdataunpaid);
		else if($eventdatapaid and empty($eventdataunpaid))
		$obj_merged=$eventdatapaid;
		else if($eventdataunpaid and empty($eventdatapaid))
		$obj_merged=$eventdataunpaid;

		$obj = new stdClass();
		if($obj_merged)
		{
			foreach($obj_merged as &$objmerged)
			{
				if(empty($objmerged->soldtickets))
				$objmerged->soldtickets=0;

				if(empty($objmerged->totaltickets))
				$objmerged->totaltickets=0;
				if($objmerged->avatar)
				{
					if($integration==2)
					{
						$objmerged->avatar=JURI::base().'media/com_jticketing/images/'.$objmerged->avatar;
					}
					else
					{
						$objmerged->avatar=JURI::base().$objmerged->avatar;
					}
				}
				else
				$eventdata3->avatar='';
				if(empty($objmerged->checkin))
				$objmerged->checkin=0;
			}
			$obj->success = "1";
			$obj->data = $obj_merged;
		}
		else
		{
			$obj->success = "0";
			$obj->message = JText::_("COM_JTICKETING_NO_EVENT_DATA_USER");
		}

		return $obj;
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
