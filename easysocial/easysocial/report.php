<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE.'/components/com_easysocial/controllers/reports.php';

class EasysocialApiResourceReport extends ApiResource
{
	public function get()
	{
	$this->plugin->setResponse(JText::_( 'PLG_API_EASYSOCIAL_USE_POST_METHOD_MESSAGE' ));			
	}	
	public function post()
	{
	$this->plugin->setResponse($this->create_report());	
	}
	public function create_report()
	{
		$app = JFactory::getApplication();
		$msg = $app->input->get('message','','STRING');
		$title = $app->input->get('user_title','','STRING');
		$item_id = $app->input->get('itemId',0,'INT');
		$log_user = $this->plugin->get('user')->id;
		$data =array();
		$data['message']= $msg;
		$data['uid']= $item_id;
		$data['type']= 'stream';
		$data['title']= $title;
		$data['extension']= 'com_easysocial';
		 //build share url use for share post through app
        $sharing = FD::get( 'Sharing', array( 'url' => FRoute::stream( array( 'layout' => 'item', 'id' => $item_id, 'external' => true, 'xhtml' => true ) ), 'display' => 'dialog', 'text' => JText::_( 'COM_EASYSOCIAL_STREAM_SOCIAL' ) , 'css' => 'fd-small' ) );
        $url = $sharing->url;
		$data['url']= $url;
		
		// Get the reports model
		$model 		= FD::model('Reports');
		// Determine if this user has the permissions to submit reports.
		$access 	= FD::access();
		// Determine if this user has exceeded the number of reports that they can submit
		$total 		= $model->getCount( array( 'created_by' => $log_user ) );
		if ($access->exceeded( 'reports.limit' , $total)) {
			$final_result['message'] =  JText::_( 'PLG_API_EASYSOCIAL_LIMIT_EXCEEDS_MESSAGE' );
			$final_result['status'] = true;
			return $final_result;
		}
		// Create the report
		$report 	= FD::table( 'Report' );
		$report->bind($data);
		// Set the creator id.
		$report->created_by=$log_user;
		// Set the default state of the report to new
		$report->state=0;
		// Try to store the report.
		$state 	= $report->store();
		// If there's an error, throw it
		if (!$state) {
			$final_result['message'] =  JText::_( 'PLG_API_EASYSOCIAL_CANT_SAVE_REPORT' );;
			$final_result['status'] = true;
			return $final_result;
		}
		
		// @badge: reports.create
		// Add badge for the author when a report is created.
		$badge 	= FD::badges();
		$badge->log( 'com_easysocial' , 'reports.create' , $log_user , JText::_( 'COM_EASYSOCIAL_REPORTS_BADGE_CREATED_REPORT' ) );

		// @points: reports.create
		// Add points for the author when a report is created.
		$points = FD::points();
		$points->assign( 'reports.create' , 'com_easysocial' , $log_user );		
	
		// Determine if we should send an email
		$config 	= FD::config();
		if ($config->get('reports.notifications.moderators')) {
			$report->notify();
		}
		$final_result['message'] = JText::_( 'PLG_API_EASYSOCIAL_REPORT_LOGGED' );;
		$final_result['status'] = true;
		return $final_result;
	}	
}	
