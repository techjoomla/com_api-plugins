<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');


class JticketApiResourceGettickettypes extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getTickettype());
	}
	
	public function post()
	{
		$this->plugin->setResponse($this->getTickettype());
	}
	
	public function getTickettype()
	{
        $lang =& JFactory::getLanguage();
		$extension = 'com_jticketing';
		$base_dir = JPATH_SITE;
		$jinput = JFactory::getApplication()->input;

		$lang->load($extension, $base_dir);
		$eventid = $jinput->get('eventid',0,'INT');
		
		if(empty($eventid))
        {
			$obj->success = 0;
			$obj->message =JText::_("COM_JTICKETING_INVALID_EVENT");
			return $obj;
		}

				$jticketingmainhelper = new jticketingmainhelper();
				$tickettypes= $jticketingmainhelper->GetTicketTypes($eventid,'');

				if($tickettypes)
				{
					foreach($tickettypes as &$tickettype)
					{
						$tickettype->available=(int)$tickettype->available;
						$tickettype->count=(int)$tickettype->count;
						$tickettype->soldticket=$tickettype->available-$tickettype->count;

						if(empty($tickettype->soldticket) or $tickettype->soldticket<0)
						{
							$tickettype->soldticket=0;
						}

						$tickettype->checkins=(int) $jticketingmainhelper->GetTicketTypescheckin($tickettype->id);
						if(empty($tickettype->checkins) or $tickettype->checkins<0)
						{
							$tickettype->checkins=0;
						}
					}

				}

        if($tickettypes)
		{
			$obj->success = "1";
			$obj->data =$tickettypes;
        }
		else
		{
			$obj->success = "0";
			$obj->message = JText::_("COM_JTICKETING_INVALID_EVENT");
		}

		$this->plugin->setResponse($obj);
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
