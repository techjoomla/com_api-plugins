<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die( 'Restricted access' );
jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';


/**
 * API class EasysocialApiResourceVotes
 *
 * @since  1.0
 */
class EasysocialApiResourceVotes extends ApiResource
{
	/**	  
	 * Function for retrieve poll data
	 * 	 
	 * @return  JSON	 
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getPollData());
	}

	/**	  
	 * Function for vote
	 * 	 
	 * @return  JSON	 
	 */
	public function post()
	{
		$this->plugin->setResponse($this->processVote());
	}

	/**	  
	 * Function for retrieve poll details
	 * 	 
	 * @return  JSON	 
	 */
	public function getPollData()
	{
		$mapp = new EasySocialApiMappingHelper;
		$app = JFactory::getApplication();
		$poll_id = $app->input->get('poll_id', 0, 'INT');
		$poll = FD::table('Polls');
		$poll->load(array('uid' => $poll_id));
		$content = $mapp->createPollData($poll->id);
		$result = get_object_vars($content);
		$poll = array();

		return $result;
	}

	/**	  
	 * Function userful for vote
	 * 	 
	 * @return  JSON	 
	 */
	public function processVote()
	{
		$app = JFactory::getApplication();
		$pollId = $app->input->get('id', 0, 'int');
		$itemId  = $app->input->get('itemId', 0, 'int');
		$action  = $app->input->get('act', '', 'default');

		return $res = $this->votescount($pollId, $itemId, $action);
	}

	/**	  
	 * Give votes to poll
	 * 
	 * @param   string  $pollId  The poll id.
	 * 
	 * @param   string  $itemId  The itemId id.
	 * 
	 * @param   string  $action  The action.
	 * 	 
	 * @return  JSON	 
	 */
	public function votescount($pollId, $itemId, $action)
	{
		$my = FD::user();
		$res = new stdClass;

		$poll = FD::table('Polls');
		$pollItem = FD::table('PollsItems');
		$state_poll_id = $poll->load($pollId);
		$state_item_id =	$pollItem->load($itemId);

		if (! $state_poll_id || !$state_item_id)
		{
			// Error. invalid poll id and poll item id.
			$res->success = 0;
			$res->message = JText::_('PLG_API_EASYSOCIAL_INVALID_VOTE_ID');

			return $res;
		}

		$pollLib = FD::get('Polls');

		// Error. if, missing any field
			if (!$pollId || !$itemId  || !$action )
			{
				$res->success = 0;
				$res->message = JText::_('PLG_API_EASYSOCIAL_VALID_DETAILS');

				return $res;
			}

			// Action vote to give vote poll item and unvote to remove vote
			if ($action == 'vote')
			{
				$result = 1;
				$resultVote  = $pollLib->vote($pollId, $itemId, $my->id);
			}
			elseif ($action == 'unvote')
			{
				$result = 0;
				$resultUnvote  = $pollLib->unvote($pollId, $itemId, $my->id);
			}
			else
			{
				$res->success = 0;
				$res->message = JText::_('PLG_API_EASYSOCIAL_VALID_DETAILS');

				return $res;
			}

			if ($result)
			{
				$res->success = 1;
				$res->message = JText::_('PLG_API_EASYSOCIAL_VOTING');
			}
			else
			{
				$res->success = 1;
				$res->message = JText::_('PLG_API_EASYSOCIAL_VOTE_REMOVED_SUCCESS');
			}

		return $res;
	}
}
