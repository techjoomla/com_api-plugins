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

class EasysocialApiResourceLike extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse("Use post or delete method.");
	}

	public function post()
	{
		//print_r($FILES);die("in post grp api");
	   $this->plugin->setResponse($this->toggleLike());
	}
	
	public function delete()
	{
		$this->plugin->setResponse("not supported to this api");
	}
	//function use for get friends data
	function toggleLike()
	{
		//init variable
		$app = JFactory::getApplication();
		
		$log_user = JFactory::getUser($this->plugin->get('user')->id);
		
		$result = new stdClass;
		
		$id = $app->input->get('id',0,'INT');
		$type = $app->input->get('type',null,'STRING'); 
		$group = $app->input->get('group','user','STRING'); 
		$itemVerb = $app->input->get('verb',null,'STRING'); 
		$streamid = $app->input->get('stream_id',0,'INT'); 

		$my = FD::user($log_user->id);
	
		// Load likes library.
		$model 		= FD::model( 'Likes' );

		// Build the key for likes
		$key		= $type . '.' . $group;
		if ($itemVerb) {
			$key = $key . '.' . $itemVerb;
		}

		// Determine if user has liked this item previously.
		$hasLiked	= $model->hasLiked( $id , $key, $my->id );

		// If user had already liked this item, we need to unlike it.
		if ($hasLiked) {
			$useStreamId = ($type == 'albums') ? '' : $streamid;

			$state 	= $model->unlike( $id , $key , $my->id, $useStreamId );

		} else {
			$useStreamId = ($type == 'albums') ? '' : $streamid;
			$state 	= $model->like( $id , $key , $my->id, $useStreamId );

			//now we need to update the associated stream id from the liked object
			if ($streamid) {
				$doUpdate = true;
				if ($type == 'photos') {
					$sModel = FD::model('Stream');
					$totalItem = $sModel->getStreamItemsCount($streamid);

					if ($totalItem > 1) {
						$doUpdate = false;
					}
				}

				if ($doUpdate) {
					$stream = FD::stream();
					$stream->updateModified( $streamid );
				}
			}
		}

		// The current action
		$verb 	= $hasLiked ? 'Unlike' : 'Like';
		
		$result->status = $state;
		$result->data = ($state && $verb == 'like')?$model->getLikesCount($id,$type):0;
		$result->message = ($state)? $verb." successful": $verb." unsuccessful";
			
		return( $result );
	}
		
}
