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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/fields.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceRequest extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getGroup());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->request());
	}
	
	public function delete()
	{
		//$this->plugin->setResponse($result);
	}
	//function use for get friends data
	function request()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		
		$group_id = $app->input->get('group_id',0,'INT');
		$req_val = $app->input->get('request','','STRING');
		$other_user_id = $app->input->get('target_user',0,'INT');
		$type = $app->input->get('type','group','STRING'); 
		
		//$userid = ($other_user_id)?$other_user_id:$log_user->id;
		$data = array();
		
		$user = FD::user($other_user_id);
		$res = new stdClass();
		
		//$mapp = new EasySocialApiMappingHelper();
	
		//$grp_model = FD::model('Groups');
		
		if(!$group_id || !$other_user_id )
		{
			$res->success = 0;
			$res->message = JText::_( 'PLG_API_EASYSOCIAL_INSUFFICIENT_INPUTS_MESSAGE' );
			return $res;
		}
		else
		{
			$group = FD::$type($group_id);

			if($group->isAdmin() != $log_user && ($req_val != 'withdraw'))
			{
				$res->success = 0;
				$res->message = JText::_( 'PLG_API_EASYSOCIAL_UNAUTHORISED_USER_MESSAGE' );
				return $res;
			}
			
			
			if($type == 'group')
			{			
				switch($req_val)
				{
					case 'Approve':
					case 'approve': $res->success = $group->approveUser( $other_user_id );
									$res->message = ($res->success)?JText::_( 'PLG_API_EASYSOCIAL_USER_REQ_GRANTED' ):JText::_( 'PLG_API_EASYSOCIAL_USER_REQ_UNSUCCESS' );
									break;
					case 'Reject':
					case 'reject' : $res->success =  $group->rejectUser( $other_user_id );
									$res->message = ($res->success)?JText::_( 'PLG_API_EASYSOCIAL_USER_APPLICATION_REJECTED' ):JText::_( 'PLG_API_EASYSOCIAL_UNABLE_REJECT_APPLICATION' );
									break;
					case 'Withdraw':
					case 'withdraw' :	$res->success = $group->deleteMember( $other_user_id );
										$res->message = ($res->success)?JText::_( 'PLG_API_EASYSOCIAL_REQUEST_WITHDRAWN' ):JText::_( 'PLG_API_EASYSOCIAL_UNABLE_WITHDRAWN_REQ' );
										break;
				}
			}
			else
			{

				$guest = FD::table('EventGuest');
        			$state = $guest->load($other_user_id);
			
				// Get the event object
        			$event = FD::event($group_id);
				$my = FD::user($log_user);

        			$myGuest = $event->getGuest();
				
				$res->success = 0;
				if (!$state || empty($guest->id))
				{
					$res->message = JText::_('COM_EASYSOCIAL_EVENTS_INVALID_GUEST_ID');
				}
				else if (empty($event) || empty($event->id)) 
				{
					$res->message = JText::_('COM_EASYSOCIAL_EVENTS_INVALID_EVENT_ID');
				}
				else if ( $myGuest->isAdmin() && $guest->isPending()) 
				{
				
					switch($req_val)
					{
						case 'Approve':
						case 'approve': 
								$res->success = $guest->approve();
								$res->message = ($res->success)?JText::_( 'PLG_API_EASYSOCIAL_USER_REQ_GRANTED' ):JText::_( 'PLG_API_EASYSOCIAL_USER_REQ_UNSUCCESS' );
										break;
						case 'Reject':
						case 'reject' : $res->success =  $guest->reject();
								$res->message = ($res->success)?JText::_( 'PLG_API_EASYSOCIAL_USER_APPLICATION_REJECTED' ):JText::_( 'PLG_API_EASYSOCIAL_UNABLE_REJECT_APPLICATION' );
										break;
						case 'remove':
						case 'Remove' :	$res->success = $guest->remove();
								$res->message = ($res->success)?JText::_( 'COM_EASYSOCIAL_EVENTS_GUEST_REMOVAL_SUCCESS' ):JText::_( 'COM_EASYSOCIAL_EVENTS_NO_ACCESS_TO_EVENT' );
											break;
					}
				}
				else
				{
					$res->message = JText::_('COM_EASYSOCIAL_EVENTS_NO_ACCESS_TO_EVENT');
				}
			}

			return $res;

		}
	}

}
