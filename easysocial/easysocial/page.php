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
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/pages.php';
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
class EasysocialApiResourcePage extends ApiResource
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
			$group[] = FD::page($group_id);
			$res->result =	$mapp->mapItem($group, 'page', $log_user->id);
			$this->plugin->setResponse($res);
		}
		else
		{
			$this->plugin->err_code = 403;
			$this->plugin->err_message = 'Page Not Found';
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
		$this->get();
	}
}
