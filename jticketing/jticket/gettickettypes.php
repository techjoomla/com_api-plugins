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
 * Class for getting ticket types based on event id
 *
 * @package     JTicketing
 * @subpackage  component
 * @since       1.0
 */
class JticketApiResourceGettickettypes extends ApiResource
{
	/**
	 * Get ticket types based on event id
	 *
	 * @return  json user list
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
		$eventid = $input->get('eventid', '0', 'INT');
		$search = $input->get('search', '', 'STRING');

		if (empty($eventid))
		{
			$obj->success = 0;
			$obj->message = JText::_("COM_JTICKETING_INVALID_EVENT");
			$this->plugin->setResponse($obj);

			return;
		}

		$jticketingmainhelper = new jticketingmainhelper;
		$tickettypes          = $jticketingmainhelper->GetTicketTypes($eventid, '', $search);

		if ($tickettypes)
		{
			foreach ($tickettypes as &$tickettype)
			{
				if ($tickettype->available == 0 || $tickettype->unlimited_seats == 1)
				{
					$tickettype->available = JText::_('COM_JTICKETING_UNLIMITED_SEATS');
				}

				$tickettype->soldticket = (int) $jticketingmainhelper->GetTicketTypessold($tickettype->id);

				if (empty($tickettype->soldticket) or $tickettype->soldticket < 0)
				{
					$tickettype->soldticket = 0;
				}

				$tickettype->checkins = (int) $jticketingmainhelper->GetTicketTypescheckin($tickettype->id);

				if (empty($tickettype->checkins) or $tickettype->checkins < 0)
				{
					$tickettype->checkins = 0;
				}
			}
		}

		$obj = new Stdclass;

		if (isset($tickettypes))
		{
			$obj->success = "1";
			$obj->data    = $tickettypes;
		}
		else
		{
			$obj->success = "0";
			$obj->message = JText::_("COM_JTICKETING_INVALID_EVENT");
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
