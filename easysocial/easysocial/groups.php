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
		$this->plugin->setResponse($this->getGroups());
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
		$this->plugin->setResponse($this->getGroups());
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
		// Init variable

		$app					=	JFactory::getApplication();
		$log_user				=	JFactory::getUser($this->plugin->get('user')->id);
		$db						=	JFactory::getDbo();
		$nxt_lim				=	20;
		$search					=	$app->input->get('search', '', 'STRING');

		// $group_id			=	$app->input->get('id', 0, 'INT');
		$userid					=	$log_user->id;
		$mapp					=	new EasySocialApiMappingHelper;
		$filters				=	array();
		$filters['category']	=	$app->input->get('category', 0, 'INT');
		$filters['uid']			=	$app->input->get('target_user', 0, 'INT');

		// Change target user
		if ($filters['uid'] != 0)
		{
			$userid	=	$filters['uid'];
		}

		$filters['types']		=	$app->input->get('type', 0, 'INT');
		$filters['state']		=	$app->input->get('state', 0, 'INT');
		$filters['featured']	=	$app->input->get('featured', false, 'BOOLEAN');
		$filters['mygroups']	=	$app->input->get('mygroups', false, 'BOOLEAN');
		$filters['invited']		=	$app->input->get('invited', false, 'BOOLEAN');
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
			$featured_grps			=	$mapp->mapItem($featured, 'group', $log_user->id);

			if (count($featured_grps) > 0 && $featured_grps != false && is_array($featured_grps))
			{
				$featured_grps		=	array_slice($featured_grps, $limitstart, $limit);

				return $featured_grps;
			}
			else
			{
				return $featured_grps;
			}
		}
		else
		{
			if ($filters['mygroups'])
			{
				$options['uid']		=	$log_user->id;
				$options['types']	=	'all';
			}

			if ($filters['invited'])
			{
				$options['invited']	=	$userid;
				$options['types']	=	'all';
			}

			if ($filters['category'])
			{
				$options['category']	=	$categoryId;
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

		return($groups);
	}

	/**
	 * Method getActor
	 *
	 * @param   string  $id  id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function getActor($id=0)
	{
		if ($id == 0)
		{
			return 0;
		}

		$user			=	JFactory::getUser($id);
		$obj			=	new stdclass;
		$obj->id		=	$user->id;
		$obj->username	=	$user->username;
		$obj->url		=	'';

		return $obj;
	}
}
