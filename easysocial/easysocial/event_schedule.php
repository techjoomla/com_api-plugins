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

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceEvent_schedule extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->get_schedule());
	}
	public function post()
	{
		$this->plugin->setResponse(JText::_( 'PLG_API_EASYSOCIAL_USE_GET_METHOD_MESSAGE' ));
	}
	//getting schedule of event
	public function get_schedule()
	{
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$event_id = $app->input->get('event_id',0,'INT');
		$event = FD::event($event_id);
		$options = array();
		
		//getting params for sending array option.
		$params = $event->getParams();		
		
		//loading model for getting event schedule.
		$schedule = FD::model('Events');
		
		//required parameters.
		$options['eventStart'] = $event->getEventStart();
        $options['end'] = $params->get('recurringData')->end;
        $options['type'] = $params->get('recurringData')->type;       
        $options['daily'] = $params->get('recurringData')->daily;       
		
		$data =	$schedule->getRecurringSchedule($options);
		//convert date in to require format.
		foreach($data as $time)
		 {			
			 $timings['schedule'][] = gmdate("Y-m-d\ TH:i:s\Z ", $time);
		 }	
		return $timings; 
	}
}
