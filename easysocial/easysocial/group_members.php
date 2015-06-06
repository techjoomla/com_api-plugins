<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');


require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groupmembers.php';
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
		$state = $app->input->get('state',1,'INT');
		$getAdmin = $app->input->get('admin',1,'INT');
		$options = array( 'groupid' => $group_id );
		$gruserob   = new EasySocialModelGroupMembers();
		$data = $gruserob->getItems($options);
		$mapp = new EasySocialApiMappingHelper();
		foreach($data as $val ) 
		{
			$val->id = $val->uid; 
		}
		$user_list = $mapp->mapItem( $data,'user',$log_user );
		
		$grp_model = FD::model('Groups');
		foreach($user_list as $user)
		{
			$user->isOwner = $grp_model->isOwner( $user->id,$group_id );
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

		/*if ($access->exceeded('groups.join', $total)) {
			$obj->success = 0;
			$obj->message = 'group joining limit exceeded';
			return $obj;
		}*/
		
		// Create a member record for the group
		$members = $group->createMember($log_user);
		
		$obj->success = 1;
		$obj->state = $members->state;
		$obj->message = 'joining application sent';
		
		return $obj;
		
	}
	
}
