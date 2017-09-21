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
 * API class EasysocialApiResourceProfile
 *
 * @since  1.0
 */
class EasysocialApiResourceProfile extends ApiResource
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
		$this->getProfile();
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
	 * Method function use for get friends data
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getProfile()
	{
		// Init variable
		$app			=	JFactory::getApplication();
		$log_user		=	$this->plugin->get('user')->id;
		$other_user_id	=	$app->input->get('user_id', 0, 'INT');
		$userid			=	($other_user_id)?$other_user_id:$log_user;

		$res = new stdClass;

		$data			=	array();
		$user			=	FD::user($userid);

		if ($user->id == 0)
		{
			$this->plugin->err_code = 404;
			$this->plugin->err_message = JText::_('COM_USERS_USER_NOT_FOUND');
			$this->plugin->setResponse(null);
		}

		// Easysocial default profile
		$profile	=	$user->getProfile();
		$mapp		=	new EasySocialApiMappingHelper;

		if ($userid)
		{
			$user_obj	=	$mapp->mapItem($userid, 'profile', $log_user);

			// Get the steps model
			$stepsModel	=	FD::model('Steps');
			$steps		=	$stepsModel->getSteps($profile->id,  SOCIAL_TYPE_PROFILES,  SOCIAL_PROFILES_VIEW_DISPLAY);

			// Get custom fields model.
			$fieldsModel	=	FD::model('Fields');

			// Get custom fields library.
			$fields		=	FD::fields();
			$field_arr	=	array();

			foreach ($steps as $step)
			{
				$step->fields	=	$fieldsModel->getCustomFields(
																	array(
																		'step_id' => $step->id,
																		'data' => true,
																		'dataId' => $userid,
																		'dataType' => SOCIAL_TYPE_USER,
																		'visible' => SOCIAL_PROFILES_VIEW_DISPLAY
																		)
																);
				$fields	=	null;

				if (count($step->fields))
				{
					$fields		=	$mapp->mapItem($step->fields, 'fields', $other_user_id, SOCIAL_FIELDS_GROUP_USER);

					if (empty($field_arr))
					{
						$field_arr	=	$fields;
					}
					else
					{
						foreach ($fields as $fld)
						{
							array_push($field_arr, $fld);
						}
					}
				}
			}

			$friendmodel = FD::model('Friends');
			$friendsObj	=	ES::friends($other_user_id, $log_user);
			$user_obj->isrequestor = $friendsObj->isRequester();

			/* $pending_req = $friendmodel->getPendingRequests($log_user);
			$user_obj->isrequestor=false;

			if($pending_req)
			{
				foreach($pending_req as $pr)
				{
					if($pr->actor_id == $other_user_id)
					$user_obj->isrequestor=true;
				}
			}
			*/

			$user_obj->more_info[] = $field_arr;
		}

		$res->result = $user_obj;

		$this->plugin->setResponse($res);
	}
}
