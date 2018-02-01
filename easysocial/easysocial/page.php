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
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function get()
	{
		// Init variable
		$app			=	JFactory::getApplication();
		$log_user		=	JFactory::getUser($this->plugin->get('user')->id);
		$page_id		=	$app->input->get('id', 0, 'INT');

		// $other_user_id	=	$app->input->get('user_id', 0, 'INT');
		// $userid			=	($other_user_id)?$other_user_id:$log_user->id;

		// $user			=	FD::user($userid);
		$mapp			=	new EasySocialApiMappingHelper;

		// $grp_model		=	FD::model('Groups');

		$res				=	new stdclass;
		$res->result		=	array();
		$res->empty_message	=	'';
		$page				=	array();

		if ($page_id)
		{
			$page[]			=	FD::page($page_id);
			$res->result	=	$mapp->mapItem($page, 'page', $log_user->id);
			$this->plugin->setResponse($res);
		}
		else
		{
			$this->plugin->err_code		=	403;
			$this->plugin->err_message	=	'PLG_API_EASYSOCIAL_PAGE_NOT_FOUND';
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
		$this->plugin->setResponse(JText::_('PLG_API_EASYSOCIAL_UNSUPPORTED_POST_METHOD_MESSAGE'));
	}

	/**
	 * Method description
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app		=	JFactory::getApplication();
		$page_id	=	$app->input->get('id', 0, 'INT');
		$valid		=	1;
		$page		=	FD::page($page_id);

		// Call groups model to get page owner
		$pagesModel =	FD::model('groups');
		$res		=	new stdclass;

		if (!$page->id || !$page_id)
		{
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_INVALID_PAGE_MESSAGE');
			$valid = 0;
		}

		// Only allow super admins to delete pages
		$my	=	FD::user($this->plugin->get('user')->id);

		if (!$my->isSiteAdmin() && !$pagesModel->isOwner($my->id, $page_id))
		{
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_PAGE_ACCESS_DENIED_MESSAGE');
			$valid				=	0;
		}

		if ($valid)
		{
			// Try to delete the page
			$page->delete();
			$res->result->status = 1;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_PAGE_DELETED_MESSAGE');
		}

		$this->plugin->setResponse($res);
	}
}
