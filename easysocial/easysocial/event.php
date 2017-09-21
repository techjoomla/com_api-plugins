<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-Plugins
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/fields.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

/**
 * API class EasysocialApiResourceEvent
 *
 * @since  1.0
 */
class EasysocialApiResourceEvent extends ApiResource
{
	/**
	 * Method get
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function get()
	{
		$this->get_event();
	}

	/**
	 * Method post
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function post()
	{
		$this->createEvent();
	}

	/**
	 * Method get event detail.
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get_event()
	{
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$event_id = $app->input->get('event_id', 0, 'INT');
		$mapp = new EasySocialApiMappingHelper;

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		if ($event_id)
		{
			$event[] = FD::event($event_id);
			$res->result = $mapp->mapItem($event, 'event', $log_user);
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method Create event
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function createEvent()
	{
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		$user = FD::user($log_user->id);
		$config	= FD::config();

		// Create group post structure
		$ev_data = array();
		$result = new stdClass;
		$valid = 1;
		$mapp = new EasySocialApiMappingHelper;
		$post['title']  = $app->input->post->get('title', null, 'STRING');
		$post['parmalink']  = $app->input->post->get('parmalink', null, 'STRING');
		$post['description']  = $app->input->post->get('description', null, 'STRING');
		$post['event_type']  = $app->input->post->get('event_type', 0, 'INT');
		$post['startDatetime']  = $app->input->post->get('startDatetime', '', 'string');
		$post['endDatetime']  = $app->input->post->get('endDatetime', '', 'string');
		$post['event_allday']  = $app->input->post->get('event_allday', 0, 'bool');
		$post['repeat']  = $app->input->post->get('repeat', array('type' => 'none', 'end' => null), 'ARRAY');
		$post['website']  = $app->input->post->get('website', 0, 'INT');
		$post['allowmaybe']  = $app->input->post->get('allowmaybe', 0, 'bool');
		$post['allownotgoingguest']  = $app->input->post->get('allownotgoingguest', 0, 'bool');
		$post['guestlimit']  = $app->input->post->get('guestlimit', 0, 'INT');
		$post['photo_albums']  = $app->input->post->get('photo_albums', 0, 'bool');
		$post['announcement']  = $app->input->post->get('announcement', 0, 'bool');
		$post['discussions']  = $app->input->post->get('discussions', 0, 'bool');
		$post['location']  = $app->input->post->get('location', null, 'STRING');
		$post['categoryId'] = $categoryId  = $app->input->post->get('category_id', 0, 'INT');
		$post['group_id'] = $app->input->post->get('group_id', null, 'INT');
		$category = FD::table('EventCategory');
		$category->load($categoryId);
		$session = JFactory::getSession();
		$session->set('category_id', $category->id, SOCIAL_SESSION_NAMESPACE);
		$stepSession = FD::table('StepSession');
		$stepSession->load(array('session_id' => $session->getId(), 'type' => SOCIAL_TYPE_EVENT));
		$stepSession->session_id = $session->getId();
		$stepSession->uid = $category->id;
		$stepSession->type = SOCIAL_TYPE_EVENT;
		$stepSession->set('step', 1);
		$stepSession->addStepAccess(1);

		$res = new stdclass;

		// Check the group access for event creation
		if (!empty($post['group_id']))
		{
			$group = FD::group($post['group_id']);

			if (!$group->canCreateEvent())
			{
				$res->result->status = 0;
				$res->result->message = JText::_('COM_EASYSOCIAL_EVENTS_NOT_ALLOWED_TO_CREATE_EVENT');

				$this->plugin->setResponse($res);
			}

			$stepSession->setValue('group_id', $post['group_id']);
		}
		else
		{
		// Check if there is a group id set in the session, if yes then remove it
			if (!empty($stepSession->values))
			{
				$value = FD::makeObject($stepSession->values);
				unset($value->group_id);
				$stepSession->values = FD::json()->encode($value);
			}
		}

		$stepSession->store();

		// Step 2 - create event
		$session = JFactory::getSession();
		$stepSession = FD::table('StepSession');
		$stepSession->load(array('session_id' => $session->getId(), 'type' => SOCIAL_TYPE_EVENT));
		$category = FD::table('EventCategory');
		$category->load($stepSession->uid);
		$sequence = $category->getSequenceFromIndex($stepSession->step, SOCIAL_EVENT_VIEW_REGISTRATION);

		// For api test purpose
		if (empty($sequence))
		{
			$res->result->success = 0;
			$res->result->message = JText::_('COM_EASYSOCIAL_EVENTS_NO_VALID_CREATION_STEP');

			$this->plugin->setResponse($res);
		}

		// Load the steps and fields
		$step = FD::table('FieldStep');
		$step->load(array('uid' => $category->id, 'type' => SOCIAL_TYPE_CLUSTERS, 'sequence' => $sequence));

		$registry = FD::registry();
		$registry->load($stepSession->values);

		// Get the fields
		$fieldsModel  = FD::model('Fields');
		$customFields = $fieldsModel->getCustomFields(array('step_id' => $step->id, 'visible' => SOCIAL_EVENT_VIEW_REGISTRATION));

		// Get from request
		$token = FD::token();
		$json  = FD::json();
		$data = $this->createData($customFields, $post);

		// Add post data in registry
		foreach ($data as $key => $value)
		{
			if ($key == $token)
			{
				continue;
			}

			if (is_array($value))
			{
				$value = $json->encode($value);
			}

			$registry->set($key, $value);
		}

		$data = $registry->toArray();
		$args = array(&$data, &$stepSession);

		// Load up the fields library so we can trigger the field apps
		$fieldsLib = FD::fields();
		$callback  = array($fieldsLib->getHandler(), 'validate');
		$stepSession->values = $json->encode($data);

		$stepSession->store();
		$completed = $step->isFinalStep(SOCIAL_EVENT_VIEW_REGISTRATION);

		$stepSession->created = FD::date()->toSql();
		$stepSession->store();

		// Here we assume that the user completed all the steps
		$eventsModel = FD::model('Events');

		// Create the new event
		$event = $eventsModel->createEvent($stepSession);

		if (!$event->id)
		{
			$errors = $eventsModel->getError();
			$res->result->status = 0;
			$res->result->message = $errors;

			$this->plugin->setResponse($res);
		}

		// Assign points to the user for creating event
		FD::points()->assign('events.create', 'com_easysocial', $log_user->id);

		// If there is recurring data, then we back up the session->values and the recurring data in the the event params first before deleting step session
		if (!empty($event->recurringData))
		{
			$clusterTable = FD::table('Cluster');
			$clusterTable->load($event->id);
			$eventParams = FD::makeObject($clusterTable->params);
			$eventParams->postdata = FD::makeObject($stepSession->values);
			$eventParams->recurringData = $event->recurringData;
			$clusterTable->params = FD::json()->encode($eventParams);
			$clusterTable->store();
		}

		$stepSession->delete();

		if ($event->isPublished() && FD::config()->get('events.stream.create'))
		{
			$event->createStream('create', $event->creator_uid, $event->creator_type);
		}

		$my = FD::user();
		$event->state = $my->getAccess()->get('events.moderate');

		if ($event->state)
		{
			$res->result->status = 1;
			$res->result->event_id = $event->id;
			$res->result->message = JText::_('COM_EASYSOCIAL_EVENTS_CREATED_PENDING_APPROVAL');
		}
		else
		{
			if ($event->id)
			{
				$res->result->status = 1;
				$res->result->event_id = $event->id;
				$res->result->message = JText::_('PLG_API_EASYSOCIAL_EVENT_CREATE_SUCCESS_MESSAGE');
			}
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method createData
	 *
	 * @param   string  $field_ids  field id
	 * @param   string  $post       post
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function createData($field_ids, $post)
	{
		$ev_data = array();
		$avtar_pth = '';
		$avtar_scr = '';
		$avtar_typ = '';
		$phto_obj = null;
		$avatar_file_name  = null;

		if (!empty($_FILES['file']['name']))
		{
			$upload_obj = new EasySocialApiUploadHelper;

			// Checking upload cover
			$phto_obj = $upload_obj->ajax_avatar($_FILES['file']);
			$avtar_pth = $phto_obj['temp_path'];
			$avtar_scr = $phto_obj['temp_uri'];
			$avtar_typ = 'upload';
			$avatar_file_name = $_FILES['file']['name'];
		}

		$cover_data = null;

		if (!empty($_FILES['cover_file']['name']))
		{
			$upload_obj = new EasySocialApiUploadHelper;

			// Checking upload cover
			$cover_data = $upload_obj->ajax_cover($_FILES['cover_file'], 'cover_file');
		}

		foreach ($field_ids as $field)
		{
				$ev_data['cid'][] = $field->id;

				switch ($field->unique_key)
				{
					case 'HEADER':
									break;
					case 'TITLE':	$ev_data['es-fields-' . $field->id] = $post['title'];
									unset($post['title']);
									break;
					case 'PERMALINK':	$ev_data['es-fields-' . $field->id] = $post['parmalink'];
									unset($post['parmalink']);
									break;
					case 'DESCRIPTION':	$ev_data['es-fields-' . $field->id] = $post['description'];
									unset($post['description']);
									break;
					case 'URL':	$ev_data['es-fields-' . $field->id] = $post['website'];
									unset($post['url']);
									break;
					case 'NEWS':	$ev_data['es-fields-' . $field->id] = $post['announcement'];
									unset($post['announcement']);
									break;
					case 'DISCUSSIONS':	$ev_data['es-fields-' . $field->id] = $post['discussions'];
									unset($post['discussions']);
									break;
					case 'ADDRESS':	$ev_data['es-fields-' . $field->id] = $post['location'];
									unset($post['location']);
									break;
					case 'RECURRING':
									$post['repeat'] = json_encode($post['repeat'], JSON_FORCE_OBJECT);
									$ev_data['es-fields-' . $field->id] = $post['repeat'];
									unset($post['repeat']);
									break;
					case 'AVATAR':	$ev_data['es-fields-' . $field->id] = Array(
																				'source' => $avtar_scr,
																				'path' => $avtar_pth,
																				'data' => '',
																				'type' => $avtar_typ,
																				'name' => $avatar_file_name
																			);
									break;
					case 'COVER':	$ev_data['es-fields-' . $field->id] = Array('data' => $cover_data, 'position' => '{"x":0.5,"y":0.5}');
									break;
				}
		}

		$ev_data = array_merge($ev_data, $post);

		return $ev_data;
	}
}
