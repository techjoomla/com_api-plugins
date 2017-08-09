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
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/fields.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceGroups
 *
 * @since  1.0
 */
class EasysocialApiResourceGroups extends ApiResource
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
		$this->getGroups();
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
	public function getGroups()
	{
		$app					=	JFactory::getApplication();
		$log_user				=	JFactory::getUser($this->plugin->get('user')->id);
		$search					=	$app->input->get('search', '', 'STRING');

		$userid					=	$log_user->id;
		$mapp					=	new EasySocialApiMappingHelper;

		$filters				=	array();
		$filters['category']	=	$app->input->get('category', 0, 'INT');
		$filters['uid']			=	$app->input->get('target_user', 0, 'INT');

		$res					=	new stdclass;
		$res->result = array();
		$res->empty_message = '';

		// Change target user
		if ($filters['uid'] != 0)
		{
			$userid	=	$filters['uid'];
		}

		$filters['types']		=	$app->input->get('type', 0, 'INT');
		$filters['state']		=	$app->input->get('state', 0, 'INT');

		$filters['all']	=	$app->input->get('all', false, 'BOOLEAN');
		$filters['featured']	=	$app->input->get('featured', false, 'BOOLEAN');
		$filters['mygroups']	=	$app->input->get('mygroups', false, 'BOOLEAN');
		$filters['invited']		=	$app->input->get('invited', false, 'BOOLEAN');

		$filters['uid'] = ($filters['mygroups']) ? $log_user->id : $filters['uid'];

		$limit					=	$app->input->get('limit', 10, 'INT');
		$limitstart				=	$app->input->get('limitstart', 0, 'INT');

		$model					=	FD::model('Groups');
		$userObj				=	FD::user($userid);
		$options				=	array('state' => SOCIAL_STATE_PUBLISHED,'ordering' => 'latest','types' => $userObj->isSiteAdmin() ? 'all' : 'user');
		$groups					=	array();

		if ($filters['featured'])
		{
			$options['featured']	=	true;
			$featured				=	$model->getGroups($options);
			$groups = $mapp->mapItem($featured, 'group', $log_user->id);

			if (count($groups) > 0 && $groups != false && is_array($groups))
			{
				$res->result = array_slice($groups, $limitstart, $limit);
				$this->plugin->setResponse($res);
			}
			$res->empty_message	=	JText::_('COM_EASYSOCIAL_GROUPS_EMPTY_FEATURED');
		}
		else
		{
			if ($filters['all']){
				$res->empty_message	=	JText::_('COM_EASYSOCIAL_GROUPS_EMPTY_ALL');
			}

			if ($filters['mygroups'])
			{
				$options['uid']		=	$log_user->id;
				$options['types']	=	'all';
				$res->empty_message	=	JText::_('COM_EASYSOCIAL_GROUPS_EMPTY_MINE');
			}

			if ($filters['invited'])
			{
				$options['invited']	=	$userid;
				$options['types']	=	'all';
				$res->empty_message	=	JText::_('COM_EASYSOCIAL_GROUPS_EMPTY_INVITED');
			}

			if ($filters['category'])
			{
				$options['category']	=	$categoryId;
				$res->empty_message	=	JText::_('COM_EASYSOCIAL_GROUPS_EMPTY_CATEGORY');
			}

			if ($filters['uid'] == 0)
			{
				$groups	=	$model->getGroups($options);
			}
			elseif ($search)
			{
				// Get exclusion list
				$exclusion	=	$app->input->get('exclusion', array(), 'array');
				$options	=	array('unpublished' => false, 'exclusion' => $exclusion);
				$groups		=	$model->getGroups($search, $options);
			}
			else
			{
				$groups		=	$model->getUserGroups($filters['uid']);
			}

			if ($limit)
			{
				$groups		=	array_slice($groups, $limitstart, $limit);
			}

			$groups	=	$mapp->mapItem($groups, 'group', $log_user->id);
		}
		if ($groups == null && $res->empty_message == '' )
		{
			$res->empty_message	=	JText::_('PLG_API_EASYSOCIAL_GROUP_NOT_FOUND');
		}
		if ($groups != null)
		{
			$res->empty_message = '';
			$res->result = $groups;
		}

		$this->plugin->setResponse($res);
	}
}
