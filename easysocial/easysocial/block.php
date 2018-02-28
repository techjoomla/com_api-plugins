<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

/** Block API 
 * 
 * @since  1.8.8
 */
class EasysocialApiResourceBlock extends ApiResource
{
	/** Get
	 * 
	 * @return	string	Error Message
	 */
	public function get()
	{
		$this->plugin->setResponse(JText::_('PLG_API_EASYSOCIAL_USE_POST_METHOD_MESSAGE'));
	}

	/** POST
	 * 
	 * @return  ApiPlugin response object
	 */
	public function post()
	{
		$this->plugin->setResponse($this->processUser());
	}

	/** POST
	 * 
	 * @return	object
	 */
	private function processUser()
	{
		$app		=	JFactory::getApplication();
		$reason		=	$app->input->get('reason', '', 'STRING');
		$target_id	=	$app->input->get('target_id', 0, 'INT');
		$block_this	=	$app->input->get('block', 0, 'INT');

		$res	=	($block_this)?$this->block($target_id, $reason):$this->unblock($target_id);

		return $res;
	}

	/** POST
	 * Block user function
	 * 
	 * @param   int     $target_id  integer for target id
	 * @param   String  $reason     string of reason to block the user
	 * 
	 * @return	object
	 */
	private function block($target_id, $reason)
	{
		$res	=	new stdClass;

		if (!$target_id)
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_INVALID_USER_MESSAGE'));
		}

		// Load up the block library
		$lib	=	ES::blocks();
		$result	=	$lib->block($target_id, $reason);

		if ($result->id)
		{
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_BLOCK_USER');
		}
		else
		{
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_BLOCK_USER_ERROR');
		}

		return $res;
	}

	/** POST
	 * Unblock user function
	 * 
	 * @param   int  $target_id  integer for target id
	 * 
	 * @return	object
	 */

	public function unblock($target_id)
	{
		$res	=	new stdClass;

		if (!$target_id)
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_INVALID_USER_MESSAGE'));
		}

		// Load up the block library
		$lib 	=	ES::blocks();
		$result	=	$lib->unblock($target_id);

		if ($result)
		{
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_UNBLOCK_USER');
		}
		else
		{
			$res->result->message	=	$result->message;
		}

		return $res;
	}
}
