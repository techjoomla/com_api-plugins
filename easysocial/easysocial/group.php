<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugins
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

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/fields.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';
/**
 * API class EasysocialApiResourceGroup
 *
 * @since  1.0
 */
class EasysocialApiResourceGroup extends ApiResource
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
		// Init variable
		$app			=	JFactory::getApplication();
		$log_user		=	JFactory::getUser($this->plugin->get('user')->id);
		$group_id		=	$app->input->get('id', 0, 'INT');
		$other_user_id	=	$app->input->get('user_id', 0, 'INT');
		$userid			=	($other_user_id)?$other_user_id:$log_user->id;

		// $user			=	FD::user($userid);
		$mapp			=	new EasySocialApiMappingHelper;

		// $grp_model		=	FD::model('Groups');

		$res				=	new stdclass;
		$res->result		=	array();
		$res->empty_message	=	'';

		if ($group_id)
		{
			$group[] = FD::group($group_id);
			$res->result =	$mapp->mapItem($group, 'group', $log_user->id);
			$this->plugin->setResponse($res);
		}
		else
		{
			$this->plugin->err_code = 403;
			$this->plugin->err_message = 'Group Not Found';
			$this->plugin->setResponse(null);
		}
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
		$this->CreateGroup();
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app		=	JFactory::getApplication();
		$group_id	=	$app->input->get('id', 0, 'INT');
		$valid		=	1;
		$group		=	FD::group($group_id);
		$groupsModel =	FD::model('groups');
		$res		=	new stdclass;

		if (!$group->id || !$group_id)
		{
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_INVALID_GROUP_MESSAGE');
			$valid = 0;
		}

		// Only allow super admins to delete groups
		$my	=	FD::user($this->plugin->get('user')->id);

		if (!$my->isSiteAdmin() && !$groupsModel->isOwner($my->id, $group_id))
		{
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_ACCESS_DENIED_MESSAGE');
			$valid				=	0;
		}

		if ($valid)
		{
			// Try to delete the group
			$group->delete();
			$res->result->status = 1;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_GROUP_DELETED_MESSAGE');
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method function for create new group
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function CreateGroup()
	{
		$app			=	JFactory::getApplication();
		$log_user		=	JFactory::getUser($this->plugin->get('user')->id);
		$user			=	FD::user($log_user->id);
		$config			=	FD::config();

		// Create group post structure
		$grp_data		=	array();
		$valid			=	1;
		$title			=	$app->input->get('title', null, 'STRING');
		$parmalink		=	$app->input->get('parmalink', null, 'STRING');
		$description	=	$app->input->get('description', null, 'STRING');
		$type			=	$app->input->get('type', 0, 'INT');
		$categoryId		=	$app->input->get('category_id', 0, 'INT');
		$avtar_pth		=	'';
		$avtar_scr		=	'';
		$avtar_typ		=	'';
		$phto_obj		=	null;

		// Response object
		$res				=	new stdclass;

		if (!empty($_FILES['file']['name']))
		{
			$upload_obj			=	new EasySocialApiUploadHelper;

			// Checking upload cover
			$phto_obj			=	$upload_obj->ajax_avatar($_FILES['file']);
			$avtar_pth			=	$phto_obj['temp_path'];
			$avtar_scr			=	$phto_obj['temp_uri'];
			$avtar_typ			=	'upload';
			$avatar_file_name	=	$_FILES['file']['name'];
		}

		$cover_data	=	null;

		if (!empty($_FILES['cover_file']['name']))
		{
			$upload_obj	=	new EasySocialApiUploadHelper;

			// Ckecking upload cover
			$cover_data	=	$upload_obj->ajax_cover($_FILES['cover_file'], 'cover_file');
		}

		// Check title
		if (empty($title) || $title == null)
		{
			$valid = 0;
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_INVALID_GROUP_NAME');
		}

		// Check parmalink
		if (empty($parmalink) || $parmalink == null)
		{
			$valid = 0;
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_INVALID_PARMALINK');
		}

		// Check description
		if (empty($description) || $description == null)
		{
			$valid = 0;
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_EMPTY_DESCRIPTION');
		}

		// Check group type
		if (empty($type) || $type == 0)
		{
			$valid = 0;
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_ADD_GROUP_TYPE_MESSAGE');
		}

		if (!$valid)
		{
			$this->plugin->setResponse($res);
		}
		else
		{
				// Create steps
				$db		=	FD::db();
				$group	=	FD::table('Group');
				FD::import('admin:/includes/group/group');
				$group	=	new SocialGroup;

				// Load front end's language file
				FD::language()->loadSite();

				$category	=	FD::table('GroupCategory');
				$category->load($categoryId);

				// Get the steps
				$stepsModel	=	FD::model('Steps');
				$steps		=	$stepsModel->getSteps($categoryId,  SOCIAL_TYPE_CLUSTERS);

				// Get the fields
				$lib			=	FD::fields();
				$fieldsModel	=	FD::model('Fields');

				// Query written due to commented function not working
				$query = "SELECT a.id, a.unique_key	FROM `#__social_fields` AS `a` 
						LEFT JOIN `#__social_apps` AS `b` ON `b`.`id` = `a`.`app_id`
						LEFT JOIN `#__social_fields_steps` AS `d` ON `a`.`step_id` = `d`.`id` 
						WHERE `a`.`step_id` = '" . $steps[0]->id . "' ORDER BY `d`.`sequence` ASC, `a`.`ordering` ASC";

				$db->setQuery($query);
				$field_ids	= $db->loadAssocList();

				foreach ($field_ids as $field)
				{
					$grp_data['cid'][] = $field['id'];

					switch ($field['unique_key'])
					{
						case 'HEADER':
										break;
						case 'TITLE':	$grp_data['es-fields-' . $field['id']] = $title;
										break;
						case 'PERMALINK':	$grp_data['es-fields-' . $field['id']] = $parmalink;
											break;
						case 'DESCRIPTION':	$grp_data['es-fields-' . $field['id']] = $description;
											break;
						case 'TYPE':	$grp_data['group_type'] = $type;
										break;
						case 'URL':		$grp_data['es-fields-' . $field['id']] = $app->input->get('website', null, 'STRING');
										break;
						case 'PHOTOS':	$grp_data['photo_albums'] = $app->input->get('photo_album', false, 'BOOLEAN');
										break;
						case 'NEWS':	$grp_data['es-fields-' . $field['id']] = $app->input->get('announcements', false, 'BOOLEAN');
										break;
						case 'DISCUSSIONS': $grp_data['es-fields-' . $field['id']] = $app->input->get('discussions', false, 'BOOLEAN');
											break;
						case 'AVATAR':	$grp_data['es-fields-' . $field['id']] = Array
												(
													'source' => $avtar_scr,
													'path' => $avtar_pth,
													'data' => '',
													'type' => $avtar_typ,
													'name' => $avatar_file_name
												);
										break;
						case 'COVER':	$grp_data['es-fields-' . $field['id']]	=	Array(
																							'data' => $cover_data,
																							'position' => '{"x":0.5, "y":0.5}'
																					);
										break;
					}
				}

				// For check group exceed limit
				if (!$user->getAccess()->allowed('groups.create') && !$user->isSiteAdmin())
				{
					$valid = 0;
					$res->result->status = 0;
					$res->result->message = JText::_('PLG_API_EASYSOCIAL_CREATE_GROUP_ACCESS_DENIED');
					$this->plugin->setResponse($res);
				}

				// Ensure that the user did not exceed their group creation limit
				if ($user->getAccess()->intervalExceeded('groups.limit', $user->id) && !$user->isSiteAdmin())
				{
					$valid = 0;
					$res->result->status = 0;
					$res->result->message = JText::_('PLG_API_EASYSOCIAL_GROUP_CREATION_LIMIT_EXCEEDS');
					$this->plugin->setResponse($res);
				}

				// Get current user's info
				$session    = JFactory::getSession();

				// Get necessary info about the current registration process.
				$stepSession	= FD::table('StepSession');
				$stepSession->load($session->getId());
				$stepSession->uid = $categoryId;

				// Load the group category
				$category 	= FD::table('GroupCategory');
				$category->load($stepSession->uid);

				$sequence = $category->getSequenceFromIndex($stepSession->step,  SOCIAL_GROUPS_VIEW_REGISTRATION);

				// Load the current step.
				$step 		= FD::table('FieldStep');
				$step->load(array('uid' => $category->id,  'type' => SOCIAL_TYPE_CLUSTERS,  'sequence' => $sequence));

				// Merge the post values
				$registry 	= FD::get('Registry');
				$registry->load($stepSession->values);

				// Load up groups model
				$groupsModel		= FD::model('Groups');

				// Get all published fields apps that are available in the current form to perform validations
				$fieldsModel 		= FD::model('Fields');
				$fields				= $fieldsModel->getCustomFields(array('step_id' => $step->id,  'visible' => SOCIAL_GROUPS_VIEW_REGISTRATION));

				// Load json library.
				$json		=	FD::json();
				$token		=	FD::token();
				$disallow	=	array($token,  'option', 'cid', 'controller', 'task', 'option', 'currentStep');

				foreach ($grp_data as $key => $value)
				{
					if (!in_array($key, $disallow))
					{
						if (is_array($value))
						{
							$value	=	FD::json()->encode($value);
						}

						$registry->set($key, $value);
					}
				}

				// Convert the values into an array.
				$data		=	$registry->toArray();
				$args		=	array(&$data, &$stepSession);

				/** Perform field validations here. Validation should only trigger apps that are loaded on the form
				* @trigger onRegisterValidate
				*/
				$fieldsLib			= FD::fields();

				// Get the trigger handler
				$handler			= $fieldsLib->getHandler();

				// Get error messages
				$errors		= $fieldsLib->trigger('onRegisterValidate', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args, array($handler, 'validate'));

				// The values needs to be stored in a JSON notation.
				$stepSession->values   = $json->encode($data);
				$stepSession->created 	= FD::date()->toMySQL();
				$group 	= $groupsModel->createGroup($stepSession);

				if ($group->id)
				{
					$res->result->status = 1;
					$res->result->id = $group->id;

					// @points: groups.create
					// Assign points to the user when a group is created
					$points = FD::points();
					$points->assign('groups.create', 'com_easysocial', $log_user);

					// If the group is published,  we need to perform other activities
					if ($group->state == SOCIAL_STATE_PUBLISHED)
					{
						$this->addTostream($user, $group, $config);
					}

					$my 			= FD::user();
					$group->state 	= $my->getAccess()->get('groups.moderate');

					if ($group->state)
					{
							$res->result->message = JText::_('COM_EASYSOCIAL_GROUPS_CREATED_PENDING_APPROVAL');
					}
					else
					{
						$res->result->message = JText::_('COM_EASYSOCIAL_GROUPS_CREATED_SUCCESSFULLY');
					}
				}
				else
				{
					$res->result->status		=	0;
					$res->result->id			=	0;
					$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNABLE_CREATE_GROUP_MESSAGE');
				}

				$this->plugin->setResponse($res);
		}
	}

	/**
	 * Method addTostream
	 *
	 * @param   array  $my      my 
	 * @param   array  $group   group object
	 * @param   array  $config  config object
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function addTostream($my, $group, $config)
	{
			$stream				=	FD::stream();
			$streamTemplate		=	$stream->getTemplate();

			// Set the actor
			$streamTemplate->setActor($my->id, SOCIAL_TYPE_USER);

			// Set the context
			$streamTemplate->setContext($group->id, SOCIAL_TYPE_GROUPS);

			$streamTemplate->setVerb('create');
			$streamTemplate->setSiteWide();
			$streamTemplate->setAccess('core.view');
			$streamTemplate->setCluster($group->id,  SOCIAL_TYPE_GROUP,  $group->type);

			// Set the params to cache the group data
			$registry	= FD::registry();
			$registry->set('group', $group);

			// Set the params to cache the group data
			$streamTemplate->setParams($registry);

			// Add stream template.
			$stream->add($streamTemplate);

			return true;
	}
}
