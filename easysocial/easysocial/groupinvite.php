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

class EasysocialApiResourceGroupinvite extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse(JText::_( 'PLG_API_EASYSOCIAL_USE_POST_OR_DELETE_MESSAGE' ));
	}

	public function post()
	{

	   $this->plugin->setResponse($this->inviteGroup());
	}
	
	public function delete()
	{
		$app = JFactory::getApplication();
		
		$group_id = $app->input->get('group_id',0,'INT');
		$target_user = $app->input->get('target_user',0,'INT');
		$operation = $app->input->get('operation',0,'STRING');
		
		$valid = 1;
		$result = new stdClass;
		
		$group	= FD::group( $group_id );

		if( !$group->id || !$group_id )
		{
			$result->status = 0;
			$result->message = JText::_( 'PLG_API_EASYSOCIAL_INVALID_GROUP_MESSAGE' );
			$valid = 0;
		}
		
		if( !$target_user )
		{
			$result->status = 0;
			$result->message = JText::_( 'PLG_API_EASYSOCIAL_INVALID_USER_MESSAGE' );
			$valid = 0;
		}

		// Only allow super admins to delete groups
		$my 	= FD::user($this->plugin->get('user')->id);


		if($target_user == $my->id && $operation == 'leave' && $group->creator_uid == $my->id )
		{
			$result->status = 0;
			$result->message = JText::_( 'PLG_API_EASYSOCIAL_GROUP_OWNER_NOT_LEAVE_MESSAGE' );
			$valid = 0;
		}
		
		//target user obj
		$user 		= FD::user( $target_user );

		if($valid)
		{
			switch($operation)
			{
				case 'leave':	// Remove the user from the group.
								$group->leave( $user->id );
								// Notify group members
								$group->notifyMembers( 'leave' , array( 'userId' => $my->id ) );
								$result->message = JText::_( 'PLG_API_EASYSOCIAL_LEAVE_GROUP_MESSAGE' );
								break;
				case 'remove':	// Remove the user from the group.
								$group->deleteMember( $user->id );
								// Notify group member
								$group->notifyMembers('user.remove', array('userId' => $user->id));
								$result->message = JText::_( 'PLG_API_EASYSOCIAL_USER_REMOVE_SUCCESS_MESSAGE' );
								break;
			}
			
			$result->status = 1;
		}
		
		$this->plugin->setResponse($result);
	}
	//function use for get friends data
	function inviteGroup()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		
		$result = new stdClass;
		
		$group_id = $app->input->get('group_id',0,'INT');
		$target_users = $app->input->get('target_users',null,'ARRAY'); 

		$user = FD::user($log_user->id);
	
		$grp_model = FD::model('Groups');
		
		$group = FD::group($group_id);
		
		if($group_id)
		{
			$not_invi = array();
			$invited = array();
			$es_params = FD::config();
			foreach ($target_users as $id) {
				
				$username = JFactory::getUser($id)->name;

				if($es_params->get('users')->displayName == 'username')
				{
					$username = $user->username;
				}
				// Ensure that the user is not a member or has been invited already
				if (!$group->isMember( $id ) && !$grp_model->isInvited($id,$group_id))
				{
					$state = $group->invite( $id, $log_user->id );
					$invited[] = $username;
				}
				else
				{
					$not_invi[] = $username;
				}
				
			}
			
			$result->status = 1;
			$result->invited = $invited;
			$result->not_invtited = $not_invi;
						
		}
		return( $result );
	}
	
}
