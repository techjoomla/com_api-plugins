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
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
/**
 * API class PlgAPIEasysocial
 *
 * @since  1.0
 */
class EasysocialApiResourceEventinvite extends ApiResource
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
		$this->plugin->setResponse(JText::_('PLG_API_EASYSOCIAL_USE_POST_METHOD_MESSAGE'));
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
		$this->plugin->setResponse($this->invite());
	}

	/**
	 * Method invite friend to event.
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function invite()
	{
		// Init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		$result = new stdClass;
		$event_id = $app->input->get('event_id', 0, 'INT');
		$target_users = $app->input->get('target_users', null, 'ARRAY');
		$user = FD::user($log_user->id);
		$event = FD::event($event_id);
		$guest = $event->getGuest($log_user->id);

		if (empty($event) || empty($event->id))
		{
			$result->message = JText::_('PLG_API_EASYSOCIAL_EVENT_NOT_FOUND_MESSAGE');
			$result->status = $state;

			return $result;
		}

		if ($event_id)
		{
			$not_invi = array();
			$invited = array();
			$es_params = FD::config();

			foreach ($target_users as $id)
			{
				$target_username = JFactory::getUser($id)->name;

				if ($es_params->get('users')->displayName == 'username')
				{
					$target_username = JFactory::getUser($id)->name;
				}

				$guest = $event->getGuest($id);

				if (! $guest->isGuest() && empty($guest->invited_by))
				{
					// Invite friend to event
					$state = $event->invite($id, $log_user->id);
					$result->message = JText::_('PLG_API_EASYSOCIAL_INVITED_MESSAGE');
					$result->status = $state;
					$invited[] = $target_username;
				}
				else
				{
					$result->message = JText::_('PLG_API_EASYSOCIAL_GUEST_CANT_INVITED_MESSAGE');
					$result->status = false;
					$not_invi[] = $target_username;
				}
			}

			$result->status = 1;
			$result->invited = $invited;
			$result->not_invtited = $not_invi;
		}

		return $result;
	}
}
