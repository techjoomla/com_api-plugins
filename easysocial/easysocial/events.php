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
class EasysocialApiResourceEvents extends ApiResource
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
		$this->getEvents();
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
		$this->plugin->setResponse($this->putStatus());
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function getEvents()
	{
		$app = JFactory::getApplication();

		// Getting log_user
		$log_user = $this->plugin->get('user')->id;
		$ordering = $this->plugin->get('ordering', 'start', 'STRING');
		$options = array();
		$eventResult = array();

		// Response object
		$res = new stdclass;

		// $res->result = array();
		$res->empty_message = '';

		$options = array(
			'state' => SOCIAL_STATE_PUBLISHED, 'ordering' => $ordering
		);
		$event = FD::model('events');
		$filter = $app->input->get('filter', 'all', 'STRING');
		$dates = $app->input->get('date', '', 'STRING');
		$cdates = $app->input->get('cdate', '', 'STRING');
		$start_date = $app->input->get('start_date', '', 'STRING');
		$end_date = $app->input->get('end_date', '', 'STRING');
		$start_before = $app->input->get('start_before', '', 'STRING');
		$includePast = $app->input->get('includePast', 0, 'INT');
		$limitstart = $app->input->get('limitstart', 0, 'INT');
		$limit = $app->input->get('limit', 0, 'INT');
		$categoryid = $app->input->get('categoryid', 0, 'INT');
		$mapp = new EasySocialApiMappingHelper;
		$userObj = FD::user($log_user);

		$options = array(
			'state' => SOCIAL_STATE_PUBLISHED, 'ordering' => $ordering, 'type' => $userObj->isSiteAdmin() ? 'all' : 'user'
		);

		if (! empty($cdates))
		{
			$dates = $cdates;
		}

		if (!empty($dates))
		{
			$check = strtotime($dates);

			if ($dates == date('Y-m', $check))
			{
				$filter = 'month';
			}
			elseif ($dates == date('Y', $check))
			{
				$filter = 'year';
			}
			elseif ($dates == date('Y-m-d', $check))
			{
				$filter = 'allDate';
			}
			else
			{
				$res->empty_message = JText::_('PLG_API_EASYSOCIAL_INVALID_DATE_FORMAT_MESSAGE');
				$res->result = [];
				$this->plugin->setResponse($res);
			}
		}

		// Checking wheather the date is in date range or it is past date.then choose appropriate case.
		if (! empty($start_date) || ! empty($end_date) || ! empty($start_before))
		{
			if ($start_before)
			{
				$filter = 'past';
			}
			else
			{
				$filter = 'range';
			}
		}

		// Get events with filter.
		switch ($filter)
		{
			case 'all':
				// We do not want to include past events here
				if (! $includePast)
				{
					$options['ongoing'] = true;
					$options['upcoming'] = true;
				}

				break;
			case 'featured':
				$options['featured'] = true;
				break;
			case 'invited':
				$options['gueststate'] = SOCIAL_EVENT_GUEST_INVITED;
				$options['guestuid'] = $log_user;
				$options['type'] = 'all';

				// We do not want to include past events here
				if (! $includePast)
				{
					$options['ongoing'] = true;
					$options['upcoming'] = true;
				}
				break;
			case 'mine':
				$options['creator_uid'] = $log_user;
				$options['creator_type'] = SOCIAL_TYPE_USER;
				$options['type'] = 'all';

				// We do not want to include past events here
				if (! $includePast)
				{
					$options['ongoing'] = true;
					$options['upcoming'] = true;
				}
				break;
			case 'range':
				$options['start-after'] = $start_date;
				$options['start-before'] = $end_date;
				break;
			case 'past':
				// $options['start-before'] = $start_before;
				$options['past'] = true;
				$options['ordering'] = 'created';
				$options['direction'] = 'desc';
				break;
			case 'allDate':
				$data = $this->dFilter($dates);
				$options['start-after'] = $data['start-after'];
				$options['start-before'] = $data['start-before'];
				break;
			case 'year':
				$data = $this->dFilter($dates);
				$options['start-after'] = $data['start-after'];
				$options['start-before'] = $data['start-before'];
				break;
			case 'month':
				$data = $this->dFilter($dates);
				$options['start-after'] = $data['start-after'];
				$options['start-before'] = $data['start-before'];
				break;
			case 'category':
				$category = FD::table('EventCategory');
				$category->load($categoryid);
				$activeCategory = $category;
				$options['category'] = $category->id;
				$options['ongoing'] = true;
				$options['upcoming'] = true;
				break;
		}

		if ($limit)
		{
			$options['limitstart'] = $limitstart;
			$options['limit'] = $limit;
		}

		$eventResult = $event->getEvents($options);

		// $eventResult = array_slice($eventResult, $limitstart, $limit);
		$event_list = $mapp->mapItem($eventResult, 'event', $log_user);
		$cat = FD::model('eventcategories');
		$res->result->events	=	$event_list;
		$res->result->categories = $cat->getCategories();

		if (!$res->result->events)
		{
			$res->empty_message = JText::_('PLG_API_EASYSOCIAL_EVENT_NOT_FOUND_MESSAGE');
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method this common function is for getting dates for month,year,today,tomorrow filters.
	 *
	 * @param   string  $dates  date
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	private function dFilter($dates)
	{
		// We need segments to be populated. If no input is passed, then it is today, and we use today as YMD then
		$segments = explode('-', $dates);
		$start = $dates;
		$end = $dates;
		$mode = count($segments);

		switch ($mode)
		{
			case 1:
				$start = $segments[0] . '-01-01';
				$end = $segments[0] . '-12-31';
				break;

			case 2:
				$start = $segments[0] . '-' . $segments[1] . '-01';

				// Need to get the month's maximum day
				$monthDate = FD::date($start);
				$maxDay = $monthDate->format('t');

				$end = $segments[0] . '-' . $segments[1] . '-' . str_pad($maxDay, 2, '0', STR_PAD_LEFT);
				break;

			default:
			case 3:
				$start = $segments[0] . '-' . $segments[1] . '-' . $segments[2];
				$end = $segments[0] . '-' . $segments[1] . '-' . $segments[2];
				break;
		}

		$options['start-after'] = $start . ' 00:00:00';
		$options['start-before'] = $end . ' 23:59:59';

		return $options;
	}

	/**
	 * Method allow user to select join/notgoing/maybe event.
	 *
	 * @return stdClass
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function putStatus()
	{
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$event_id = $app->input->get('event_id', 0, 'INT');
		$state = $app->input->get('state', '', 'STRING');
		$res = new stdClass;

		// Load the user
		$user = FD::user($log_user);

		// Load the event
		$event = FD::event($event_id);

		// Determine the guest object
		$guest = $event->getGuest($log_user);

		if (empty($event) || empty($event->id) || ! $event->isPublished())
		{
			$res->message = JText::_('PLG_API_EASYSOCIAL_EVENT_NOT_FOUND_MESSAGE');
			$res->status = 0;

			return $res;
		}

		if (($event->isClosed() && ((! $guest->isParticipant() && $state !== 'request') || ($guest->isPending() && $state !== 'withdraw')))
			||($event->isInviteOnly() && ! $guest->isParticipant()))
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_ERROR_MESSAGE'));
		}

		$guest->cluster_id = $event_id;
		$access = $user->getAccess();
		$total = $user->getTotalEvents();

		if (in_array($state, array('going', 'maybe', 'request')) && $access->exceeded('events.join', $total))
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_LIMIT_EXCEEDS_MESSAGE'));
		}

		switch ($state)
		{
			case 'going':
				$final = $guest->going();
				break;

			case 'notgoing':
				if ($event->getParams()->get('allownotgoingguest', true) || $guest->isOwner())
				{
					$final = $guest->notGoing();
				}
				else
				{
					$final = $guest->withdraw();
				}
				break;

			case 'maybe':
				$final = $guest->maybe();
				break;

			case 'request':
				$final = $guest->request();
				break;

			case 'withdraw':
				$final = $guest->withdraw();
				break;

			default:
				$final = JText::_('PLG_API_EASYSOCIAL_SELECT_VALID_OPTION_MESSAGE');
				break;
		}

		$res->status = $final;

		return $res;
	}
}
