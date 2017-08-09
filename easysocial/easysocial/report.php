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

require_once JPATH_SITE . '/components/com_easysocial/controllers/reports.php';
/**
 * API class EasysocialApiResourceReport
 *
 * @since  1.0
 */
class EasysocialApiResourceReport extends ApiResource
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
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_EASYSOCIAL_USE_POST_METHOD_MESSAGE');
		$this->plugin->setResponse(null);
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
		$this->create_report();
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function create_report()
	{
		$app				=	JFactory::getApplication();
		$msg				=	$app->input->get('message', '', 'STRING');
		$title				=	$app->input->get('user_title', '', 'STRING');
		$item_id			=	$app->input->get('itemId', 0, 'INT');
		$log_user			=	$this->plugin->get('user')->id;
		$data				=	array();
		$data['message']	=	$msg;
		$data['uid']		=	$item_id;
		$data['type']		=	$app->input->get('type', 'stream', 'STRING');
		$data['title']		=	$title;
		$data['extension']	=	'com_easysocial';

		// Response Object
		$res = new stdClass;

		// Build share url use for share post through app

		switch ($data['type'])
		{
			case 'stream':
					$sharing = FD::get('Sharing', array('url' => FRoute::stream(
																				array(
																					'layout' => 'item',
																					'id' => $item_id,
																					'external' => true,
																					'xhtml' => true
																				)
																				),
															'display' => 'dialog',
															'text' => JText::_('COM_EASYSOCIAL_STREAM_SOCIAL'),
															'css' => 'fd-small'
														)
									);
					$url = $sharing->url;
					$data['url'] = $url;
			break;
			case 'groups':
					$group	= FD::group($item_id);
					$data['url'] = $group->getPermalink(false, true);
			break;
			case 'event':

			$event = FD::event($item_id);
			$data['url'] = $event->getPermalink(false, true);
			break;
			case 'profile':
			$user	= FD::user($item_id);
			$data['url'] = $user->getPermalink(false, true);
			break;
			case 'photos':
			$ptable	= FD::table('Photo');
			$ptable->load($item_id);
			$data['url'] = $ptable->getPermalink();
			break;
			case 'albums':
				$atable	= FD::table('Album');
				$atable->load($item_id);
				$data['url'] = $atable->getPermalink();
			break;
		}

		// Get the reports model
		$model = FD::model('Reports');

		// Determine if this user has the permissions to submit reports.
		$access 	= FD::access();

		// Determine if this user has exceeded the number of reports that they can submit
		$total 		= $model->getCount(array('created_by' => $log_user));

		if ($access->exceeded('reports.limit', $total))
		{
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_LIMIT_EXCEEDS_MESSAGE');
			$res->result->status = false;
			$this->plugin->setResponse($res);
		}

		// Create the report
		$report 	= FD::table('Report');
		$report->bind($data);

		// Set the creator id.
		$report->created_by = $log_user;

		// Set the default state of the report to new
		$report->state = 0;

		// Try to store the report.
		$state 	= $report->store();

		// If there's an error, throw it
		if (!$state)
		{
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_CANT_SAVE_REPORT');
			$res->result->status = false;
			$this->plugin->setResponse($res);
		}

		// @badge: reports.create Add badge for the author when a report is created.
		$badge 	= FD::badges();
		$badge->log('com_easysocial', 'reports.create', $log_user, JText::_('COM_EASYSOCIAL_REPORTS_BADGE_CREATED_REPORT'));

		// @points: reports.create Add points for the author when a report is created.
		$points = FD::points();
		$points->assign('reports.create', 'com_easysocial', $log_user);

		// Determine if we should send an email
		$config 	= FD::config();

		if ($config->get('reports.notifications.moderators'))
		{
			$report->notify();
		}

		$res->result->message = JText::_('COM_EASYSOCIAL_REPORTS_STORED_SUCCESSFULLY');
		$res->result->status = true;

		$this->plugin->setResponse($res);
	}
}
