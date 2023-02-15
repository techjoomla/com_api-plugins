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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;


require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/fields.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

/**
 * API class EasysocialApiResourceRequest
 *
 * @since  1.0
 */
class EasysocialApiResourceRequest extends ApiResource
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
		$this->plugin->setResponse(Text::_('PLG_API_EASYSOCIAL_UNSUPPORTED_METHOD_MESSAGE'));
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
		$this->plugin->setResponse($this->request());
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return	stdClass
	 *
	 * @since 1.0
	 */
	public function request()
	{
		// Init variable
		$app		= Factory::getApplication();
		$log_user	= Factory::getUser($this->plugin->get('user')->id);
		$clusterId	= $app->input->get('id', 0, 'INT');
		$req_val	= $app->input->get('request', '', 'STRING');
		$other_user_id	= $app->input->get('target_user', 0, 'INT');
		$type		= $app->input->get('type', 'group', 'STRING');

		$data = array();
		$req_val = ($req_val == 'cancel')?'reject':$req_val;

		$user = FD::user($other_user_id);
		$res  = new stdClass;

		if (!$clusterId || !$other_user_id)
		{
			$res->success = 0;
			$res->message = Text::_('PLG_API_EASYSOCIAL_INSUFFICIENT_INPUTS_MESSAGE');

			return $res;
		}
		else
		{
			$group = FD::$type($clusterId);

			if ($group->isClient("administrator") != $log_user && ($req_val != 'withdraw') && ($req_val != 'reject'))
			{
				$res->success = 0;
				$res->message = Text::_('PLG_API_EASYSOCIAL_UNAUTHORISED_USER_MESSAGE');

				return $res;
			}

			if ($type == 'group')
			{
				switch ($req_val)
				{
					case 'Approve':
					case 'approve':
						$res->success = $group->approveUser($other_user_id);
						$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_USER_REQ_GRANTED') :
						Text::_('PLG_API_EASYSOCIAL_USER_REQ_UNSUCCESS');
						break;
					case 'Reject':
					case 'reject':
						$res->success = $group->rejectUser($other_user_id);
						$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_USER_APPLICATION_REJECTED') :
						Text::_('PLG_API_EASYSOCIAL_UNABLE_REJECT_APPLICATION');
						break;
					case 'Withdraw':
					case 'withdraw':
						$res->success = $group->deleteMember($other_user_id);
						$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_REQUEST_WITHDRAWN') : Text::_('PLG_API_EASYSOCIAL_UNABLE_WITHDRAWN_REQ');
						break;
				}
			}
			elseif ($type == 'page')
			{
				switch ($req_val)
				{
					case 'Approve':
					case 'approve':
						$res->success = $group->approveUser($other_user_id);
						$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_PAGE_USER_REQ_GRANTED') :
						Text::_('PLG_API_EASYSOCIAL_PAGE_USER_REQ_UNSUCCESS');
						break;
					case 'Reject':
					case 'reject':
						$res->success = $group->rejectUser($other_user_id);
						$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_PAGE_USER_APPLICATION_REJECTED') :
						Text::_('PLG_API_EASYSOCIAL_UNABLE_REJECT_APPLICATION');
						break;
					case 'Withdraw':
					case 'withdraw':
						$res->success = $group->deleteMember($other_user_id);
			$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_PAGE_REQUEST_WITHDRAWN') : Text::_('PLG_API_EASYSOCIAL_PAGE_UNABLE_WITHDRAWN_REQ');
						break;
				}
			}
			else
			{
				$guest = FD::table('EventGuest');
				$state = $guest->load($other_user_id);
				$event = FD::event($clusterId);
				$my    = FD::user($log_user->id);
				$myGuest = $event->getGuest();
				$res->success = 0;

				if (!$state || empty($guest->id))
				{
					$res->message = Text::_('COM_EASYSOCIAL_EVENTS_INVALID_GUEST_ID');
				}
				elseif (empty($event) || empty($event->id))
				{
					$res->message = Text::_('COM_EASYSOCIAL_EVENTS_INVALID_EVENT_ID');
				}
				elseif ($myGuest->isClient("administrator") && $guest->isPending())
				{
					switch ($req_val)
					{
						case 'Approve':
						case 'approve':
							$res->success = $guest->approve();
							$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_USER_REQ_GRANTED') : Text::_('PLG_API_EASYSOCIAL_USER_REQ_UNSUCCESS');
							break;
						case 'Reject':
						case 'reject':
							$res->success = $guest->reject();
							$res->message = ($res->success) ? Text::_('PLG_API_EASYSOCIAL_USER_APPLICATION_REJECTED') :
							Text::_('PLG_API_EASYSOCIAL_UNABLE_REJECT_APPLICATION');
							break;
						case 'remove':
						case 'Remove':
							$res->success = $guest->remove();
							$res->message = ($res->success) ? Text::_('COM_EASYSOCIAL_EVENTS_GUEST_REMOVAL_SUCCESS') :
							Text::_('COM_EASYSOCIAL_EVENTS_NO_ACCESS_TO_EVENT');
							break;
					}
				}
				else
				{
					$res->message = Text::_('COM_EASYSOCIAL_EVENTS_NO_ACCESS_TO_EVENT');
				}
			}

			return $res;
		}
	}
}
