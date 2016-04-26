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


class EasysocialApiResourceBlock extends ApiResource
{
	public function get()
	{
	$this->plugin->setResponse("Use method post");
	}	
	public function post()
	{
		$this->plugin->setResponse($this->processUser());
	}
	
	public function processUser()
	{
		$app = JFactory::getApplication();
		$reason = $app->input->get('reason','','STRING');
		$target_id = $app->input->get('target_id',0,'INT');
		$block_this = $app->input->get('block',0,'INT');
		//print_r($_POST);die();
		return $res = ($block_this)?$this->block($target_id,$reason):$this->unblock($target_id);
		
	}
	
	//block user function
	public function block($target_id,$reason)
	{
		
		//print_r($target_id);die( 'block access' );
		$res = new stdClass();
	
		if(!$target_id)
		{
			$res->success = 0;
			$res->message = JText::_('COM_EASYSOCIAL_INVALID_USER_ID_PROVIDED');
		}

		// Load up the block library
		$lib = FD::blocks();
		$result = $lib->block($target_id, $reason);

		if($result->id)
		{
			$res->success = 1;
			$res->message = JText::_('COM_EASYSOCIAL_USER_BLOCKED_SUCCESSFULLY');
		}
		else
		{
			$res->success = 0;
			$res->message = JText::_('Unable to block error');
		}
		return $res;
	}	
	
	public function unblock($target_id)
	{
		//print_r($target_id);
		$res = new stdClass();
		
		if(!$target_id)
		{
			$res->success = 0;
			$res->message = JText::_('COM_EASYSOCIAL_INVALID_USER_ID_PROVIDED');
			return $res;
		}

		// Load up the block library
		$lib = FD::blocks();
		$result = $lib->unblock($target_id);

		if($result)
		{
			$res->success = 1;
			$res->message = JText::_('COM_EASYSOCIAL_USER_UNBLOCKED_SUCCESSFULLY');
		}
		else
		{
			$res->success = $result->code;
			$res->message = $result->message;
		}
		return $res;
	}

}	
