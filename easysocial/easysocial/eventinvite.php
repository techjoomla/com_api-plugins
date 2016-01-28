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
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';

class EasysocialApiResourceEventinvite extends ApiResource
{
	public function get()
	{		
		$this->plugin->setResponse(JText::_( 'PLG_API_EASYSOCIAL_USE_POST_METHOD_MESSAGE' ));
	}
	public function post()
	{
		$this->plugin->setResponse($this->invite());
	}
	//invite friend to event.
	public function invite()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		$result = new stdClass;
		$event_id = $app->input->get('event_id',0,'INT');
		$target_users = $app->input->get('target_users',null,'ARRAY'); 
		$user = FD::user($log_user->id);
		$event = FD::event($event_id);
		$guest = $event->getGuest($log_user);
		if (empty($event) || empty($event->id))
		{
			$result->message=JText::_( 'PLG_API_EASYSOCIAL_EVENT_NOT_FOUND_MESSAGE' );
			$result->status=$state;
			return $result;
		}
		if($event_id)
		{			
			$not_invi = array();
			$invited = array();	
			
			foreach ($target_users as $id)
			{
				$guest = $event->getGuest($id);
				if (!$guest->isGuest() && empty($guest->invited_by)) {					
					//invite friend to  event
					$state = $event->invite($id,$log_user->id);
					$result->message=JText::_( 'PLG_API_EASYSOCIAL_INVITED_MESSAGE' );
					$result->status=$state;
					$invited[] = JFactory::getUser($id)->username;					
				}
				else
				{
					$result->message=JText::_( 'PLG_API_EASYSOCIAL_GUEST_CANT_INVITED_MESSAGE' );
					$result->status=$state;
					$not_invi[] = JFactory::getUser($id)->username;					
				}
			}
			$result->status = 1;
			$result->invited = $invited;
			$result->not_invtited = $not_invi;
		}
		return $result;
	}
}
