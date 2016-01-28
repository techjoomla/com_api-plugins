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

class EasysocialApiResourceLike extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse(JText::_( 'PLG_API_EASYSOCIAL_USE_POST_OR_DELETE_MESSAGE' ));
	}

	public function post()
	{
		//print_r($FILES);die("in post grp api");
	   $this->plugin->setResponse($this->toggleLike());
	}	
	public function delete()
	{
		$this->plugin->setResponse(JText::_( 'PLG_API_EASYSOCIAL_NOT_SUPPORTED_MESSAGE' ));
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
		$useStreamId = ($type == 'albums') ? '' : $streamid;

		// If user had already liked this item, we need to unlike it.
		if ($hasLiked) {

			$state 	= $model->unlike( $id , $key , $my->id, $useStreamId );

		} else {
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
		$verb 	= $hasLiked ? JText::_( 'PLG_API_EASYSOCIAL_UNLIKE' ) : JText::_( 'PLG_API_EASYSOCIAL_LIKE' );
		
		$result->status = $state;
		$result->data = ($state && $verb == 'like')?$model->getLikesCount($id,$type):0;
		$result->message = ($state)? $verb.JText::_( 'PLG_API_EASYSOCIAL_SUCCESSFULL' ): $verb.JText::_( 'PLG_API_EASYSOCIAL_UNSUCCESSFULL' );
			
		return( $result );
	}
		
}
