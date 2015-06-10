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

class EasysocialApiResourceFriends extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getFriends());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->getFriends());
	}
	//function use for get friends data
	function getFriends()
	{
		$avt_model = FD::model( 'Avatars' );
		$default = $avt_model->getDefaultAvatars(0,$type = SOCIAL_TYPE_PROFILES); 
		print_r($default);die("in api");
		//init variable
		$app = JFactory::getApplication();
		$user = JFactory::getUser($this->plugin->get('user')->id);
		$userid = $app->input->get('target_user',0,'INT');

		$search = $app->input->get('search','','STRING');
		
		$mapp = new EasySocialApiMappingHelper();

		if($userid == 0)
		$userid = $user->id;
		
		$frnd_mod = FD::model( 'Friends' );

		// if search word present then search user as per term and given id
		if(empty($search))
		{
			$ttl_list = $frnd_mod->getFriends($userid); 
	    }
	    else
	    {
			$ttl_list = $frnd_mod->search($userid,$search,'username');
		}
		
	    $frnd_list = $mapp->mapItem( $ttl_list,'user',$userid);

	    //get other data
	    foreach($frnd_list as $ky=>$lval)
	    {	
			//get mutual friends of given user
			if($userid != $user->id)
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($user->id,$lval->id);
				$lval->isFriend = $frnd_mod->isFriends($user->id,$lval->id);
				//$lval->mutual_frnds = $frnd_mod->getMutualFriends($userid,$lval->id);
			}
			else
			{
				$lval->mutual = $frnd_mod->getMutualFriendCount($userid,$lval->id);
				$lval->isFriend = $frnd_mod->isFriends($user->id,$lval->id);
			}

			//$lval->mutual = $frnd_mod->getMutualFriendCount($user->id,$lval->id);
			//$lval->isFriend = $frnd_mod->isFriends($user->id,$lval->id);
		}

		return( $frnd_list );
	}
}
