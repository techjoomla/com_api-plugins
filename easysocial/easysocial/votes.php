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


class EasysocialApiResourceVotes extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getPollData());		
	}	
	public function post()
	{	
		$this->plugin->setResponse($this->processVote());	
	}
    
    /* Function for retrieve poll details.*/	
	public function getPollData(){
		
		$mapp = new EasySocialApiMappingHelper();
		$app = JFactory::getApplication();		
		$poll_id = $app->input->get('poll_id',0,'INT');
	
		
		$poll = FD::table( 'Polls' );
		$poll->load(array('uid'=>$poll_id));
		
		$content = $mapp->createPollData($poll->id);
		$result = get_object_vars($content);
		$poll = array();
		return $result;
		
		
	}
	
	public function processVote()
	{
		$app = JFactory::getApplication();
		$pollId = $app->input->get('id', 0, 'int');
		$itemId  = $app->input->get('itemId', 0, 'int');
        $action  = $app->input->get('act', '', 'default');
        	
		return $res = $this->votescount($pollId, $itemId, $action);
	}
	// give votes to poll	
	public function votescount($pollId, $itemId, $action)
	{
		$my = FD::user();
		$res = new stdClass();
		
		$poll = FD::table('Polls');	
		$pollItem = FD::table('PollsItems');
		$state_poll_id = $poll->load($pollId);		
		$state_item_id =	$pollItem->load($itemId);


		if (! $state_poll_id || !$state_item_id) {
			// error. invalid poll id and poll item id.
			$res->success = 0;
			$res->message = JText::_('PLG_API_EASYSOCIAL_INVALID_VOTE_ID');
			return $res;
		}

		$pollLib = FD::get('Polls');	
		// error. if, missing any field
		if (!$pollId || !$itemId  || !$action ){
				$res->success = 0;
				$res->message = JText::_('PLG_API_EASYSOCIAL_VALID_DETAILS');
				return $res;
			}
			
			//action vote to give vote poll item and unvote to remove vote
			if ($action == 'vote') {
				$result = 1;
				$resultVote  = $pollLib->vote($pollId, $itemId, $my->id);	
			} else if ($action == 'unvote') {
				$result = 0;
				$resultUnvote  = $pollLib->unvote($pollId, $itemId, $my->id);
			}else{
					$res->success = 0;
					$res->message = JText::_('PLG_API_EASYSOCIAL_VALID_DETAILS');
					return $res;
				}
				
			
			if($result){
				$res->success = 1;
				$res->message = JText::_('PLG_API_EASYSOCIAL_VOTING');
			}else{
				$res->success = 1;
				$res->message =JText::_('PLG_API_EASYSOCIAL_VOTE_REMOVED_SUCCESS');
			}
		
		return $res;
	}

}	

