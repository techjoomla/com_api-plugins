<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');


class JticketApiResourceGeteventdetails extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getEvents());
	}
	
	public function post()
	{
		$this->plugin->setResponse($this->getEvents());
	}
	
	public function getEvents()
	{
		$jinput = JFactory::getApplication()->input;
		$com_params=JComponentHelper::getParams('com_jticketing');
		//0 Jomsocial 1/Native 3 JEvents
		$integration = $com_params->get('integration');
        $extension = 'com_jticketing';
		$base_dir = JPATH_SITE;
		
		$jticketingmainhelper = new jticketingmainhelper();
		$lang = JFactory::getLanguage();
		$lang->load($extension, $base_dir);
		
		$eventid = $jinput->get('eventid',0,'INT');

		if(empty($eventid))
        {
			$obj->success = 0;
			$obj->message =JText::_("COM_JTICKETING_INVALID_EVENT");
			return $obj;
		}

		$eventdatapaid = $jticketingmainhelper->GetUserEventsAPI('',$eventid);
		$eveidarr=array();

		if($eventdatapaid)
		{
			foreach($eventdatapaid as &$eventdata)
			{
				$eveidarr[]=$eventdata->id;
				$eventdata->totaltickets= $jticketingmainhelper->GetTicketcount($eventdata->id);
			}
		}

		$eventdataunpaid= $jticketingmainhelper->GetUser_unpaidEventsAPI($userid,$eveidarr,$eventid);

		if($eventdataunpaid )
		{
			foreach($eventdataunpaid as &$eventdata3)
			{
				$eventdata3->totaltickets= $jticketingmainhelper->GetTicketcount($eventdata3->id);
				$eventdata3->soldtickets=0;
				$eventdata3->checkin=0;
			}
		}

		if($eventdatapaid and $eventdataunpaid)
		{
			$obj_merged =array_merge((array) $eventdatapaid, (array) $eventdataunpaid);
		}
		else if($eventdatapaid and empty($eventdataunpaid))
		{
			$obj_merged=(array)$eventdatapaid;
		}
		else if($eventdataunpaid and empty($eventdatapaid))
		{
			$obj_merged=(array)$eventdataunpaid;
		}

        $obj = new stdClass();
        if($obj_merged)
		{
			$config = JFactory::getConfig();
			$return=$jticketingmainhelper->getTimezoneString($eventdata->id);
			$obj_merged[0]->startdate=$return['startdate'];
			$obj_merged[0]->enddate=$return['enddate'];
			$datetoshow=$return['startdate'].'-'.$return['enddate'];
			if(!empty($return['eventshowtimezone']))
			$datetoshow.='<br/>'.$return['eventshowtimezone'];

			if($obj_merged[0]->avatar)
				{
					if($integration==2)
					{
						$obj_merged[0]->avatar=JURI::base().'media/com_jticketing/images/'.$obj_merged[0]->avatar;
					}
					else
					{
						$obj_merged[0]->avatar=JURI::base().$obj_merged[0]->avatar;
					}
				}
			else
				$obj_merged[0]->avatar='';
			if(empty($obj_merged[0]->soldtickets))
			{
				$obj_merged[0]->soldtickets=0;
			}

			if(empty($obj_merged[0]->totaltickets))
			{
				$obj_merged[0]->totaltickets=0;
			}

			if(empty($obj_merged[0]->checkin))
			{
				$obj_merged[0]->checkin=0;
			}

			$obj->success = "1";
			$obj->data =$obj_merged;

		}
		else
		{
			$obj->success = "0";
			$obj->data = JText::_("COM_JTICKETING_NO_EVENT_DATA");
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
