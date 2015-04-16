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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/fields.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceProfile extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getProfile());
	}

	public function post()
	{
		//print_r($FILES);die("in post grp api");
	   $this->plugin->setResponse("use get method");
	}
	
	//function use for get friends data
	function getProfile()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		
		$other_user_id = $app->input->get('user_id',0,'INT'); 
		
		$userid = ($other_user_id)?$other_user_id:$log_user;

		$data = array();
		
		$user = FD::user($userid);
		
		//easysocial default profile
		$profile = $user->getProfile();

		$mapp = new EasySocialApiMappingHelper();
		
		
		if($userid)
		{
			$user_obj = $mapp->mapItem($userid,'profile',$log_user);
			//$user_obj = $mapp->createUserObj($userid);
			/*
			$user_obj = $mapp->createUserObj($userid);
			$user_obj->isself = ($log_user == $other_user_id )?true:false;
			$user_obj->cover = $user->getCover();
			
			if( $log_user != $other_user_id )
			{
				$log_user_obj = FD::user( $log_user );
				$user_obj->isfriend = $log_user_obj->isFriends( $other_user_id );
				$user_obj->isfollower = $log_user_obj->isFollowed( $other_user_id );
				//$user_obj->approval_pending = $user->isPending($other_user_id);
			}
			$user_obj->friend_count = $user->getTotalFriends();
			$user_obj->follower_count = $user->getTotalFollowers();
			$user_obj->badges = $user->getBadges();
			$user_obj->points = $user->getPoints();
			*/
			//code for custom fields
			// Get the steps model
			$stepsModel = FD::model('Steps');
			$steps = $stepsModel->getSteps($profile->id, SOCIAL_TYPE_PROFILES, SOCIAL_PROFILES_VIEW_EDIT);

			// Get custom fields model.
			$fieldsModel = FD::model('Fields');
			// Get custom fields library.
			$fields = FD::fields();
			$field_arr = array();
			foreach ($steps as $step)
			{

				$step->fields = $fieldsModel->getCustomFields(array('step_id' => $step->id, 'data' => true, 'dataId' => $userid, 'dataType' => SOCIAL_TYPE_USER, 'visible' => 'edit'));
				$fields = null;
				if(count($step->fields))
				{
					$fields = $mapp->mapItem($step->fields,'fields',$userid);
					//die("in fields loop");
					if(empty($field_arr))
					{
						$field_arr = $fields;
					}
					else
					{
						foreach($fields as $fld)
						{
							array_push( $field_arr,$fld );
						}
						//array_merge( $field_arr,$fields );
					}
				}
			
			}
			
			$user_obj->more_info = $field_arr; 

		}
		return( $user_obj );
	}
	
}
