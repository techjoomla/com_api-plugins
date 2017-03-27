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
jimport('libraries.schema.group');
jimport('joomla.html.html');
FD::import('site:/controllers/controller');

/**
 * API class PlgAPIEasysocial
 *
 * @since  1.0
 */
class EasysocialApiResourcePollsOne extends ApiResource
{
	/**
	 * Method processAction
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->plugin->setResponse($this->processAction());
	}

	/**
	 * Method processAction
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->plugin->setResponse($this->processAction());
	}

	/**
	 * Method processAction
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function processAction()
	{
		$app      = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		$filterType = $app->input->get('type', 'hidden', 'STRING');
		$isloadmore = $app->input->get('loadmore', '', '');
		$limitstart = $app->input->get('limitstart', '0', '');
		$context    = SOCIAL_STREAM_CONTEXT_TYPE_ALL;
		$my         = FD::user();
		$stream     = FD::stream();
		$activities = $stream->getActivityLogs(
												array(
														'uId' => $log_user,
														'context' => $context,
														'filter' => $filterType,
														'limitstart' => $limitstart
												)
											);

		$nextlimit = $stream->getActivityNextLimit();

		return $activities;
	}
}
