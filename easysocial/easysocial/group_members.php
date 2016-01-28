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


require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groupmembers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/model.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceGroup_members extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getGroup_Members());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->joineGroup());
	}
	
	public function getGroup_Members()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;		
		$group_id = $app->input->get('group_id',0,'INT');
		$limitstart = $app->input->get('limitstart',0,'INT');
		$limit = $app->input->get('limit',10,'INT');
		$mapp = new EasySocialApiMappingHelper();
		$data = array();
		//hari
		$filter = $app->input->get('filter','going','STRING');		
	
		if($limitstart)
		{
			$limit = $limit + $limitstart;
		}
		
		//for filter user by type
		$type = $app->input->get('type','group','STRING');
		
		$state = $app->input->get('state',1,'INT');
		$getAdmin = $app->input->get('admin',1,'INT');

		if($type == 'group')
		{		
			$options = array( 'groupid' => $group_id );
			$gruserob   = new EasySocialModelGroupMembers();

			$gruserob->setState('limit',$limit);

			$data = $gruserob->getItems($options);

			foreach($data as $val ) 
			{
				$val->id = $val->uid; 
			}
		
		}
		else if( $type == 'event' )
		{
			//hari
			//Get event guest with filter.
			if(!empty($filter))
			{			
				switch($filter)
				{
					case 'going':
									$options['state'] = SOCIAL_EVENT_GUEST_GOING;
					break;
					case 'notgoing':
									$options['state'] = SOCIAL_EVENT_GUEST_NOT_GOING;
					break;
					case 'maybe': 
									$options['state'] = SOCIAL_EVENT_GUEST_MAYBE;
										
					break;
					case 'admins':
									$options['admin'] = true;
					break;
				}
				$options['users'] = true;
				$eguest = FD::model('Events');	
				$data = $eguest->getGuests($group_id,$options);
			}

		}
		
		if(empty($data))
                {
                    $ret_arr = new stdClass;
                    $ret_arr->status = false;
                    $ret_arr->message = JText::_( 'PLG_API_EASYSOCIAL_MEMBER_NOT_FOUND_MESSAGE' );
                    return $ret_arr;
                }		

		$user_list = $mapp->mapItem( $data,'user',$log_user );
		
		$grp_model = FD::model('Groups');
		foreach($user_list as $user)
		{
			$user->isMember = $grp_model->isMember( $user->id,$group_id );
			$user->isOwner = $grp_model->isOwner( $user->id,$group_id );
			$user->isInvited = $grp_model->isInvited( $user->id,$group_id );
			$user->isPendingMember = $grp_model->isPendingMember( $user->id,$group_id );
		}
		
		//manual pagination code
		$user_list = array_slice( $user_list, $limitstart, $limit );
		return $user_list;
	}
	//join group by user
	public function joineGroup()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$group_id = $app->input->get('group_id',0,'INT');
		$obj = new stdClass();
		
		$group	= FD::group( $group_id );
		
		// Get the user's access as we want to limit the number of groups they can join
		$user = FD::user($log_user);
		$access = $user->getAccess();
		$total = $user->getTotalGroups();

		if ($access->exceeded('groups.join', $total)) {
			$obj->success = 0;
			$obj->message = JText::_( 'PLG_API_EASYSOCIAL_GROUP_JOIN_LIMIT_EXCEEDS_MESSAGE' );
			return $obj;
		}
		
		if(!$group->isMember( $log_user ))
		{
		// Create a member record for the group
		$members = $group->createMember($log_user);
		
		$obj->success = 1;
		$obj->state = $members->state;
		//$obj->message = 'Great! Your request has been sent successfully and it is pending approval from the site administrator.';
		 if($group->type==1)
               {                        
                       $obj->message = 'Welcome to the group, since this is an open group, you are automatically a member of the group now.';
               }
               else
               {
                       $obj->message = 'Great! Your request has been sent successfully and it is pending approval from the  group administrator.';
               }

		}
		else
		{
		$obj->success = 0;
		$obj->state = $members->state;
		$obj->message = JText::_( 'PLG_API_EASYSOCIAL_GROUP_ALREADY_JOINED_MESSAGE' );
		}
		
		return $obj;
		
	}
	
}
