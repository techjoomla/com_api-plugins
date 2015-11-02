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

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceComments extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getComments());
	}

	public function post()
	{
		$app = JFactory::getApplication();
		
		$element = $app->input->get('element', '', 'string');
		$group = $app->input->get('group', '', 'string');
		$verb = $app->input->get('verb', '', 'string');
		$uid = $app->input->get('uid', 0, 'int');//element id

		$input = $app->input->get( 'comment', "" ,'RAW');
		$params = $app->input->get( 'params',array(),'ARRAY');//params
		$streamid = $app->input->get( 'stream_id', '' , 'INT');//whole stream id
		$parent = $app->input->get( 'parent', 0 ,'INT');//parent comment id

		$result = new stdClass;
		$valid = 1;
		
		if(!$uid)
		{
			$result->id = 0;
			$result->status  = 0;
			$result->message = 'Empty element id not allowed';
			$valid = 0;
		}

		// Message should not be empty.
		if(empty($input))
		{
			$result->id = 0;
			$result->status  = 0;
			$result->message = 'Empty comment not allowed';
			$valid = 0;
		}
		else if($valid)
		{
				// Normalize CRLF (\r\n) to just LF (\n)
				$input = str_ireplace("\r\n", "\n", $input );
		
				$compositeElement = $element . '.' . $group . '.' . $verb;

				$table = FD::table('comments');

				$table->element = $compositeElement;
				$table->uid = $uid;
				$table->comment = $input;
				$table->created_by = FD::user()->id;
				$table->created = FD::date()->toSQL();
				$table->parent = $parent;
				$table->params = $params;
				$table->stream_id = $streamid;

				$state = $table->store();

				if ($streamid)
				{
					$doUpdate = true;
					if ($element == 'photos')
					 {
						$sModel = FD::model('Stream');
						$totalItem = $sModel->getStreamItemsCount($streamid);

						if ($totalItem > 1) {
								$doUpdate = false;
							}
					}

						if ($doUpdate) {
							$stream = FD::stream();
							$stream->updateModified( $streamid, FD::user()->id, SOCIAL_STREAM_LAST_ACTION_COMMENT);
						}
				}

				if($state)
				{
					$dispatcher = FD::dispatcher();

					$comments = array(&$table);
					$args = array( &$comments );

					// @trigger: onPrepareComments
					$dispatcher->trigger($group, 'onPrepareComments', $args);
				  	
				  	//create result obj    
					$result->status  = 1;
					$result->message = 'comment saved successfully';    
				}
				else
				{
					//create result obj    
					$result->status  = 0;
					$result->message = 'Unable to save comment'; 
				}
			
		}
		
	   $this->plugin->setResponse($result);
	}
	
	public function getComments()
	{
		$app = JFactory::getApplication();
		
		$row = new stdClass();
		$row->uid = $app->input->get('uid',0,'INT');
		$row->element = $app->input->get('element','','STRING');//discussions.group.reply
		$row->stream_id = $app->input->get('stream_id',0,'INT');
		$row->group = $app->input->get('group','','STRING');
		$row->verb = $app->input->get('verb','','STRING');
		
		$row->limitstart = $app->input->get('limitstart',0,'INT');
		$row->limit = $app->input->get('limit',10,'INT');
		
		//$row->deleteable = 1;
		//$row->parentid = 0;

		$data = array();
		$mapp = new EasySocialApiMappingHelper();
		$data['data'] = $mapp->createCommentsObj( $row ,$row->limitstart,$row->limit );
		
		return $data;
	}
	
	public function delete()
	{
		$app = JFactory::getApplication();
		
		$conversion_id = $app->input->get('conversation_id',0,'INT');
		$valid = 1;
		$result = new stdClass;
	
		if( !$conversion_id )
		{
			
			$result->status = 0;
			$result->message = 'Invalid Conversations';
			$valid = 0;
		}
		
		if($valid)
		{
			// Try to delete the group
			$conv_model = FD::model('Conversations');
			//$my 	= FD::user($this->plugin->get('user')->id);
			$result->status = $conv_model->delete( $conversion_id , $this->plugin->get('user')->id );
			$result->message = 'Conversations deleted successfully';
		}
		
		$this->plugin->setResponse($result);
	}
}
