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
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/friends.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/users.php';

class EasysocialApiResourceManage_friends extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->manageFriends());
	}

	public function post()
	{
		
	   $this->plugin->setResponse($this->manageFriends());
	}
	//function use for get friends data
	function manageFriends()
	{
		//init variable
		$app = JFactory::getApplication();
		
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		
		$db = JFactory::getDbo();
		
		$frnd_id = $app->input->post->get('friend_id',0,'INT');
		$choice = $app->input->post->get('choice',0,'INT');
		
		$userid = $log_user->id;
		
		$res = new stdClass();
		
		if(!$frnd_id)
		{
			return 'Friend id not found';
		}
		
		if( $choice )
		{
			$frnds_obj = new EasySocialModelFriends();
			
			$result = $frnds_obj->request($frnd_id,$userid);
			
			if($result->id)
			{
				$res->frnd_id = $frnd_id;
				$res->code = 200;
				$res->message = 'Request send';
			}
			else
			{
				$res->code = 403;
				$res->message = $result;
			}
			
			
		}
		else
		{
			$user 	= FD::user( $id );
			$user->approve();
			
			$res->result = EasySocialModelUsers::deleteFriends($frnd_id);
			
			if( $res->result == true )
			{
				 $res->code = 200;
				 $res->message = 'Freind deleted';
			}
			else
			{
				$res->code = 403;
				$res->message = 'Unable to delete friend';
			}
			
		}
		
		return $res;
		
	}
}
