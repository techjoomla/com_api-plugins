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
use Joomla\Registry\Registry;
JLoader::register("EasySocialApiUploadHelper", JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php');
JLoader::register("EasySocialApiMappingHelper", JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php');


ES::import('fields:/group/permalink/helper');

/**
 * API class EasysocialApiResourceGroup
 *
 * @since  1.0
 */
class EasysocialApiResourceGroup extends ApiResource
{
	/**
	 * Method to get details of single Easysocial group
	 *
	 * @return  ApiPlugin response object
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$input = JFactory::getApplication()->input;

		// Get the group object
		$id		=	$input->get('id', 0, 'int');
		$user	=	ES::user();
		$group	=	ES::group($id);

		// Ensure that the id provided is valid
		if (! $group || ! $group->id)
		{
			ApiError::raiseError(400, JText::_('COM_EASYSOCIAL_GROUPS_INVALID_GROUP_ID'));
		}

		// Ensure that the user has access to view group's item
		if (! $group->canViewItem() || !$group->isPublished() || ($user->id != $group->creator_uid && $user->isBlockedBy($group->creator_uid)))
		{
			ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_GROUPS_NO_ACCESS'));
		}

		$EasySocialApiMappingHelper = new EasySocialApiMappingHelper;

		$apiResponse = new stdclass;
		$apiResponse->result = array();
		$apiResponse->empty_message = '';

		$apiResponse->result = $EasySocialApiMappingHelper->mapItem([$group], 'group', $user->id);
		$this->plugin->setResponse($apiResponse);
	}

	/**
	 * Method to create/update single Easysocial group
	 *
	 * @return  ApiPlugin response object
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$user	=	ES::user();
		$input	=	JFactory::getApplication()->input;
		$id		=	$input->get('id', 0, 'int');

		$apiResponse			=	new stdClass;
		$apiResponse->result	=	new stdClass;
		$postValues				=	$input->post->getArray();

		// Check EasySocial extension version
		$version = ES::getLocalVersion();

		if (empty($postValues['title']))
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_INVALID_GROUP_NAME'));
		}

		// Check parmalink
		if (empty($postValues['permalink']))
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_INVALID_PARMALINK'));
		}

		// Check description
		if (empty($postValues['description']))
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_EMPTY_DESCRIPTION'));
		}

		// Check group type
		if (empty($postValues['type']))
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_ADD_GROUP_TYPE_MESSAGE'));
		}

		// Flag to see if this is new or edit
		$isNew = empty($id);

		if ($isNew)
		{
			if (! $user->getAccess()->allowed('groups.create') && ! $user->isSiteAdmin())
			{
				ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_GROUPS_NO_ACCESS_CREATE_GROUP'));
			}

			// Ensure that the user did not exceed their group creation limit
			if ($user->getAccess()->intervalExceeded('groups.limit', $user->id) && ! $user->isSiteAdmin())
			{
				ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_GROUPS_EXCEEDED_LIMIT'));
			}

			if ( $version >= '2.1.0')
			{
				$this->validateGroupPermalink($postValues['permalink']);
			}

			// Load the group category
			$category = ES::table('GroupCategory');
			$category->load($postValues['category_id']);

			if (! $category->id || ($category->type != SOCIAL_FIELDS_GROUP_GROUP))
			{
				ApiError::raiseError(400, JText::_('COM_EASYSOCIAL_GROUPS_INVALID_CATEGORY_ID'));
			}

			// Check if the user really has access to create groups
			if (! $user->getAccess()->allowed('groups.create') && ! $user->isSiteAdmin())
			{
				ApiError::raiseError(403, JText::_('COM_EASYSOCIAL_GROUPS_NO_ACCESS_CREATE_GROUP'));
			}

			// Need to check if this clsuter category has creation limit based on user points or not.
			if (! $category->hasPointsToCreate($user->id))
			{
				ApiError::raiseError(400, JText::_('COM_EASYSOCIAL_GROUPS_INSUFFICIENT_POINTS'));
			}

			$options				=	array();

			if ( $version >= '2.1.0')
			{
				$options['workflow_id']	=	$category->getWorkflow()->id;
			}
			else
			{
				$stepsModel	=	ES::model('Steps');
				$steps		=	$stepsModel->getSteps($postValues['category_id'],  SOCIAL_TYPE_CLUSTERS);
				$options['step_id']	=	$steps[0]->id;
			}

			$options['group']		=	SOCIAL_FIELDS_GROUP_GROUP;

			// Get fields model
			$fieldsModel = ES::model('Fields');

			// Special case for group AVTAR and COVER
			$files = $input->post->files;

			// Retrieve all file objects if needed

			if ($files->get('cover'))
			{
				$uploadObj = new EasySocialApiUploadHelper;
				$coverMeta = $uploadObj->ajax_cover($files->get('cover'), 'cover', $category->id, SOCIAL_TYPE_GROUP);

				if (empty($coverMeta))
				{
					ApiError::raiseError(400, $uploadObj->getError());
				}

				$postValues['cover'] = $coverMeta;
			}

			if ($files->get('avatar'))
			{
				$uploadObj = new EasySocialApiUploadHelper;
				$avtarMeta = $uploadObj->ajax_avatar($files->get('avatar'), 'avatar', $category->id, SOCIAL_TYPE_GROUP);

				if (empty($avtarMeta))
				{
					ApiError::raiseError(400, $uploadObj->getError());
				}

				$postValues['avatar'] = array(
					'source' => $avtarMeta['temp_uri'], 'path' => $avtarMeta['temp_path'], 'data' => '', 'type' => 'upload',
						'name' => $files->get('avatar')['name']
				);
			}

			// Get the custom fields
			$fields = $fieldsModel->getCustomFields($options);

			// Now map the post values with Easysocial custom fields
			$fieldArray = $this->createFieldArr($postValues, $options);

			// Get current user's info
			$session = JFactory::getSession();

			// Get necessary info about the current registration process.
			$stepSession = ES::table('StepSession');
			$stepSession->load($session->getId());
			$stepSession->uid = $category->id;

			// Step is eliminated from here since it is single step registration

			// Merge the post values
			$registry = ES::get('Registry');
			$registry->load($stepSession->values);

			// Load up groups model
			$groupsModel = ES::model('Groups');

			// Load json library.
			$json = ES::json();

			// $post = $input->post->getArray();

			$disallow = array(
				'option', 'cid', 'controller', 'task', 'option', 'currentStep'
			);

			// Process $_POST vars
			foreach ($fieldArray as $key => $value)
			{
				if (! in_array($key, $disallow))
				{
					if (is_array($value))
					{
						$value = ES::json()->encode($value);
					}

					$registry->set($key, $value);
				}
			}

			// Convert the values into an array.
			$data = $registry->toArray();
			$args = array(
				&$data, 'conditionalRequired' => '', &$stepSession
			);

			// Perform field validations here. Validation should only trigger apps that are loaded on the form
			// @trigger onRegisterValidate
			$fieldsLib = ES::fields();

			// Format conditional data
			$fieldsLib->trigger('onConditionalFormat', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args);

			// Rebuild the arguments since the data is already changed previously.
			$args = array(
				&$data, 'conditionalRequired' => '', &$stepSession
			);

			// Some data need to be retrieved in raw value. let fire another trigger. #730
			$fieldsLib->trigger('onFormatData', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args);

			// Get the trigger handler
			$handler = $fieldsLib->getHandler();

			// Get error messages
			$errors = $fieldsLib->trigger('onRegisterValidate', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args, array($handler, 'validate'));

			// The values needs to be stored in a JSON notation.
			$stepSession->values = $json->encode($data);

			// Store registration into the temporary table.
			$stepSession->store();

			// Bind any errors into the registration object
			$stepSession->setErrors($errors);

			// Saving was intercepted by one of the field applications.
			if (is_array($errors) && count($errors) > 0)
			{
				ApiError::raiseError(400, JText::_('COM_EASYSOCIAL_REGISTRATION_SOME_ERRORS_IN_THE_REGISTRATION_FORM'));
			}

			// Update creation date
			$stepSession->created = ES::date()->toMySQL();

			// Save the temporary data.
			$stepSession->store();

			// Create the group now.
			$group = $groupsModel->createGroup($stepSession);

			// If there's no id, we know that there's some errors.
			if (! $group->id)
			{
				$errors = $groupsModel->getError();
				ApiError::raiseError(400, $errors);
			}

			$points = ES::points();
			$points->assign('groups.create', 'com_easysocial', $user->id);

			// Add this action into access logs.
			ES::access()->log('groups.limit', $user->id, $group->id, SOCIAL_TYPE_GROUP);
			$message = JText::_('COM_EASYSOCIAL_GROUPS_CREATED_PENDING_APPROVAL');

			// If the group is published, we need to perform other activities
			if ($group->state == SOCIAL_STATE_PUBLISHED)
			{
				$message = JText::_('COM_EASYSOCIAL_GROUPS_CREATED_SUCCESSFULLY');
				$this->addTostream($user, $group);

				// Update social goals
				$user->updateGoals('joincluster');
			}

			$apiResponse->result->status = 1;
			$apiResponse->result->message = $message;

			$this->plugin->setResponse($apiResponse);
		}
		else
		{
			$group = ES::group($id);

			if (!$id || !$group->id)
			{
				ApiError::raiseError(400, JText::_("COM_EASYSOCIAL_GROUPS_INVALID_GROUP_ID"));
			}

			if (!$group->isOwner() && !$group->isAdmin() && !$user->isSiteAdmin())
			{
				ApiError::raiseError(400, JText::_("COM_EASYSOCIAL_GROUPS_NO_ACCESS"));
			}

			if (! SocialFieldsGroupPermalinkHelper::valid($postValues['permalink'], new Registry))
			{
				ApiError::raiseError(400, JText::_('PLG_FIELDS_GROUP_PERMALINK_INVALID_PERMALINK'));
			}

			$this->validateGroupPermalink($postValues['permalink'], $group);

			// Special case for group AVTAR and COVER
			$files = $input->post->files;

			// Retrieve all file objects if needed
			if ($files->get('cover'))
			{
				$uploadObj = new EasySocialApiUploadHelper;
				$coverMeta = $uploadObj->ajax_cover($files->get('cover'), 'cover', $group->category_id, SOCIAL_TYPE_GROUP);

				if (empty($coverMeta))
				{
					ApiError::raiseError(400, $uploadObj->getError());
				}

				$postValues['cover'] = $coverMeta;
			}

			if ($files->get('avatar'))
			{
				$uploadObj = new EasySocialApiUploadHelper;
				$avtarMeta = $uploadObj->ajax_avatar($files->get('avatar'), 'avatar', $group->category_id, SOCIAL_TYPE_GROUP);

				if (empty($avtarMeta))
				{
					ApiError::raiseError(400, $uploadObj->getError());
				}

				$postValues['avatar'] = array(
						'source' => $avtarMeta['temp_uri'], 'path' => $avtarMeta['temp_path'], 'data' => '', 'type' => 'upload',
						'name' => $files->get('avatar')['name']
				);
			}

			$fieldsModel = ES::model('Fields');

			$options = array(
			'group' => SOCIAL_TYPE_GROUP ,
			'workflow_id' => $group->getWorkflow()->id,
			'data' => true, 'dataId' => $group->id,
			'dataType' => SOCIAL_TYPE_GROUP,
			'visible' => SOCIAL_PROFILES_VIEW_EDIT);

			$fields = $fieldsModel->getCustomFields($options);
			$groupData = $this->generateGroupFieldData($fields);
			$postValues = $this->createFieldArr($postValues, $options);
			$registry = ES::registry();

			foreach ($groupData as $key => $value)
			{
				if (isset($postValues[$key]))
				{
					$value = $postValues[$key];
				}

				if (is_array($value))
				{
					$value = json_encode($value);
				}

				$registry->set($key, $value);
			}

			$data = $registry->toArray();
			$fieldsLib	= ES::fields();
			$handler = $fieldsLib->getHandler();

			// @TODO Add conditional field support
			$args = array(&$data, 'conditionalRequired' => '', &$group);
			$fieldsLib->trigger('onConditionalFormat', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args, array($handler));
			$args = array(&$data, 'conditionalRequired' => '', &$group);
			$errors = $fieldsLib->trigger('onAdminEditValidate', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args, array($handler, 'validate'));

			if (is_array($errors) && count($errors) > 0)
			{
				ApiError::raiseError(400, JText::_("COM_EASYSOCIAL_GROUPS_PROFILE_SAVE_ERRORS"));
			}

			$errors = $fieldsLib->trigger('onEditBeforeSave', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args, array($handler, 'beforeSave'));

			if (is_array($errors) && count($errors) > 0)
			{
				ApiError::raiseError(400, JText::_("COM_EASYSOCIAL_PROFILE_ERRORS_IN_FORM"));
			}

			if ($group->isDraft() || $user->getAccess()->get('groups.moderate'))
			{
				$group->state = SOCIAL_CLUSTER_PENDING;
			}

			if ($user->isSiteAdmin())
			{
				$group->state = SOCIAL_CLUSTER_PUBLISHED;
			}

			$group->save();
			$args = array(&$data, &$group);
			$fieldsLib->trigger('onEditAfterSave', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args);
			$group->bindCustomFields($data);
			$args = array(&$data, &$group);
			$fieldsLib->trigger('onEditAfterSaveFields', SOCIAL_FIELDS_GROUP_GROUP, $fields, $args);

			if ($group->isPublished())
			{
				$points = ES::points();
				$points->assign('groups.update', 'com_easysocial', $user->id);
				$group->createStream($user->id, 'update');
			}

			$messageLang = $group->isPending() ? 'COM_EASYSOCIAL_GROUPS_UPDATED_PENDING_APPROVAL' : 'COM_EASYSOCIAL_GROUPS_PROFILE_UPDATED_SUCCESSFULLY';

			$apiResponse->result->status = 1;
			$apiResponse->result->message = JText::_($messageLang);

			$this->plugin->setResponse($apiResponse);
		}
	}

	/**
	 * Method to delete single Easysocial group
	 *
	 * @return  ApiPlugin response object
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$input = JFactory::getApplication()->input;

		$user = ES::user();

		// Get the group
		$id = $input->get('id', 0, 'int');
		$group = ES::group($id);

		if (! $group->id || ! $id)
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_INVALID_GROUP_MESSAGE'));
		}

		// Only group owner and site admins are allowed to delete the group
		if (! $user->isSiteAdmin() && ! $group->isOwner())
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_ACCESS_DENIED_MESSAGE'));
		}

		// Try to delete the group
		$group->delete();

		$apiResponse = new stdclass;

		$apiResponse->result->status = 1;
		$apiResponse->result->message = JText::_('PLG_API_EASYSOCIAL_GROUP_DELETED_MESSAGE');

		$this->plugin->setResponse($apiResponse);
	}

	/**
	 * Method to create stream on Easysocial wall
	 *
	 * @param   Object  $user    SocialUser Object
	 * @param   Object  $group   SocialGroup Object
	 * @param   array   $config  config object
	 *
	 * @return  boolean
	 *
	 * @since 1.0
	 */
	public function addTostream($user, $group, $config)
	{
		// Add activity logging when a user creates a new group.
		$stream = ES::stream();
		$streamTemplate = $stream->getTemplate();

		// Set the actor
		$streamTemplate->setActor($user, SOCIAL_TYPE_USER);

		// Set the context
		$streamTemplate->setContext($group->id, SOCIAL_TYPE_GROUPS);

		$streamTemplate->setVerb('create');
		$streamTemplate->setSiteWide();
		$streamTemplate->setAccess('core.view');
		$streamTemplate->setCluster($group->id, SOCIAL_TYPE_GROUP, $group->type);

		// Set the params to cache the group data
		$registry = ES::registry();
		$registry->set('group', $group);

		// Set the params to cache the group data
		$streamTemplate->setParams($registry);

		// Add stream template.
		$stream->add($streamTemplate);

		return true;
	}

	/**
	 * create field array as per easysocial format for storing custom field data
	 *
	 * @param   Object  $postValues    The group post data
	 * @param   Object  $fieldsOption  The options required to get field data
	 *
	 * @return  Array
	 *
	 * @since 1.0
	 */
	public function createFieldArr($postValues, $fieldsOption)
	{
		$userfields = array();
		$fieldsModel = ES::model('Fields');

		foreach ($postValues as $key => $value)
		{
			$fieldsOption['key'] = $key;

			$localfield = $fieldsModel->getCustomFields($fieldsOption);

			if (! empty($localfield))
			{
				switch ($key)
				{
					case 'cover':
						$userfields[SOCIAL_FIELDS_PREFIX . $localfield['0']->id] = array(
							'data' => $value, 'position' => '{"x":0, "y":0}'
						);
						break;
					default:
						$userfields[SOCIAL_FIELDS_PREFIX . $localfield['0']->id] = $value;
				}
			}
		}

		return $userfields;
	}

	/**
	 * create field array as per easysocial format for updating custom field data
	 *
	 * @param   Array  $fields  The array SocialFields class object
	 *
	 * @return  Array
	 *
	 * @since 2.0
	 */
	private function generateGroupFieldData($fields)
	{
		$groupFields = array();

		if (!empty($fields))
		{
			foreach ($fields as $field)
			{
				$groupFields[SOCIAL_FIELDS_PREFIX . $field->id] = $field->data;
			}
		}

		return $groupFields;
	}

	/**
	 * Check the permalink value provided for the group
	 *
	 * @param   String  $permalink  The permalink value
	 * @param   array   $group      The SocialGroup class object
	 *
	 * @return  boolean
	 *
	 * @since 2.0
	 */
	private function validateGroupPermalink($permalink, $group = array())
	{
		if (!empty($group))
		{
			// If the permalink is the same, just return true.
			if ($group->alias == $permalink)
			{
				return true;
			}
		}

		if (!SocialFieldsGroupPermalinkHelper::allowed($permalink))
		{
			ApiError::raiseError(400, JText::_('PLG_FIELDS_PERMALINK_CONFLICTS_WITH_SYSTEM'));
		}

		// Peramalink validation
		if (SocialFieldsGroupPermalinkHelper::exists($permalink))
		{
			ApiError::raiseError(400, JText::_('PLG_FIELDS_GROUP_PERMALINK_NOT_AVAILABLE'));
		}

		if (! SocialFieldsGroupPermalinkHelper::valid($permalink, new Registry))
		{
			ApiError::raiseError(400, JText::_('PLG_FIELDS_GROUP_PERMALINK_INVALID_PERMALINK'));
		}

		return true;
	}
}
