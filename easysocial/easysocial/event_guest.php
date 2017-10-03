<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
/**
 * API class PlgAPIEasysocial
 *
 * @since  1.0
 */
class EasysocialApiResourceEvent_Guest extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->plugin->setResponse($this->get_guests());
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_EASYSOCIAL_USE_GET_METHOD_MESSAGE');
		$this->plugin->setResponse(null);
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get_guests()
	{
		$app = JFactory::getApplication();

		// Getting log_user.
		$log_user = $this->plugin->get('user')->id;

		// Get event id,limit,limitstart.
		$event_id = $app->input->get('event_id', 0, 'INT');
		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 10, 'INT');
		$options = array();
		$ordering = $this->plugin->get('ordering', 'name', 'STRING');
		$state = $app->input->get('state', '', 'STRING');
		$mapp = new EasySocialApiMappingHelper;
		$eguest = FD::model('Events');

		// Filter with guests state.
		switch ($state)
		{
			case 'going':
				$options['state'] = SOCIAL_EVENT_GUEST_GOING;
				break;
			case 'notgoing':
				$options['state'] = SOCIAL_EVENT_GUEST_NOT_GOING;
				break;
			case 'maybe':
				$options['state'] = SOCIAL_EVENT_GUEST_MAYBE;

				break;
			case 'admins':
				$options['admin'] = true;
				break;
		}

		$options['users'] = true;
		$options['limitstart'] = $limitstart;
		$options['limit'] = $limit;
		$res = $eguest->getGuests($event_id, $options);

		// Map the object to userobject.
		$eventGuests = $mapp->mapItem($res, 'user');

		return $eventGuests;
	}
}
