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
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/router.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/apps.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceReply extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getDiscussionReply());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->postDiscussionReply());   
	}
	
	public function delete()
	{
		/*
		$result = new stdClass;
		$group_id = $app->input->get('id',0,'INT');
		$appId = $app->input->get('discussion_id',0,'INT');

		$discussion	= FD::table( 'Discussion' );
		$discussion->load( $appId );

		$my 		= FD::user();
		$group 		= FD::group( $group_id );

		if( !$group->isAdmin() && $discussion->created_by != $this->plugin->get('user')->id)
		{
			//error message;
			return false;
		}

		// Delete the discussion
		$res  = $discussion->delete();
		$result->status = ($res)?'Conversation deleted successfully':'Unable to delete converstion.';
		$this->plugin->setResponse($result);
		*/
	}
	//function use for get friends data
	function getDiscussionReply()
	{
		//init variable
		$mainframe = JFactory::getApplication();
		
		$group_id = $mainframe->input->get('group_id',0,'INT');
		$discussId = $mainframe->input->get('discussion_id',0,'INT');
		$wres = new stdClass;
		$valid = 0;

		if(!$group_id)
		{
			$wres->status = 0;
			$wres->message[] = 'Group id is empty';
			return $wres;
		}
		else
		{
			$group 		= FD::group( $group_id );
			
			// Get the current filter type
			$filter 	= $mainframe->input->get('filter','all','STRING');
			
			$options 	= array();

			if( $filter == 'unanswered' )
			{
				$options[ 'unanswered' ]	= true;
			}

			if( $filter == 'locked' )
			{
				$options[ 'locked' ]	= true;
			}

			if( $filter == 'resolved' )
			{
				$options[ 'resolved' ]	= true;
			}
			
			$options[ 'limit' ]	= $mainframe->input->get('limit',10,'INT');
			
			$options[ 'ordering' ] = 'created';
			
			$mapp = new EasySocialApiMappingHelper();
			
			$model 			= FD::model( 'Discussions' );
			$reply_rows	= $model->getReplies( $discussId,$options);
			
			if($discussId)
			{
				$disc_dt = new stdClass();
				//create discussion details as per request
				$discussion = FD::table( 'Discussion' );
				$discussion->load( $discussId );
				$data_node[] = $discussion; 
				$data['discussion'] = $mapp->mapItem($data_node,'discussion',$this->plugin->get('user')->id);
			}
			
			$data['data'] = $mapp->mapItem($reply_rows,'reply',$this->plugin->get('user')->id);
			
			//
			return( $data );
		}
	}
	
	//function for create new group
	function postDiscussionReply()
	{
		//init variable
		$mainframe = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		// Load the discussion
		$discuss_id 	= $mainframe->input->get('discussion_id',0,'INT');
		$groupId 	= $mainframe->input->get('group_id',0,'INT');
		$content 	= $mainframe->input->get('content','','STRING');
		
		$wres = new stdClass;
		
		$discussion = FD::table( 'Discussion' );
		$discussion->load( $discuss_id );
		
		// Get the current logged in user.
		$my		= FD::user($log_user);

		// Get the group
		$group		= FD::group( $groupId );
		
		$reply 				= FD::table( 'Discussion' );
		$reply->uid 		= $discussion->uid;
		$reply->type 		= $discussion->type;
		$reply->content 	= $content;
		$reply->created_by 	= $log_user;
		$reply->parent_id 	= $discussion->id;
		$reply->state 		= SOCIAL_STATE_PUBLISHED;

		// Save the reply.
		$state = $reply->store();
		
		if($state)
		{	
			$this->createStream($discussion,$group,$reply,$log_user);
			$wres->id = $discussion->id;
			$wres->message[] = 'Discussion reply posted successfuly';
			return $wres;
		}
		
	}
	
	public function createStream($discussion,$group,$reply,$log_user)
	{
				// Create a new stream item for this discussion
		$stream = FD::stream();
		$my		= FD::user($log_user);
		// Get the stream template
		$tpl		= $stream->getTemplate();

		// Someone just joined the group
		$tpl->setActor( $log_user , SOCIAL_TYPE_USER );

		// Set the context
		$tpl->setContext( $discussion->id , 'discussions' );

		// Set the cluster
		$tpl->setCluster( $group->id , SOCIAL_TYPE_GROUP, $group->type );

		// Set the verb
		$tpl->setVerb( 'reply' );

		// Set the params to cache the group data
		$registry 	= FD::registry();
		$registry->set( 'group' , $group );
		$registry->set( 'reply' , $reply );
		$registry->set( 'discussion' , $discussion );

		$tpl->setParams( $registry );

		$tpl->setAccess('core.view');

		// Add the stream
		$stream->add( $tpl );

		// Update the parent's reply counter.
		$discussion->sync( $reply );

		// Before we populate the output, we need to format it according to the theme's specs.
		$reply->author 		= $my;

		// Load the contents
		$theme 		= FD::themes();

		// Since this reply is new, we don't have an answer for this item.
		$answer 	= false;

		$theme->set('question', $discussion);
		$theme->set('group', $group);
		$theme->set('answer', $answer);
		$theme->set('reply', $reply);

//		$contents	= $theme->output( 'apps/group/discussions/canvas/item.reply' );

		// Send notification to group members
		$options 	= array();
		//$options[ 'permalink' ]	= FRoute::apps( array( 'layout' => 'canvas' , 'customView' => 'item' , 'uid' => $group->getAlias() , 'type' => SOCIAL_TYPE_GROUP , 'id' => $this->getApp()->getAlias() , 'discussionId' => $discussion->id , 'external' => true ) , false );
		$options[ 'permalink' ]	= FRoute::apps( array( 'layout' => 'canvas' , 'customView' => 'item' , 'uid' => $group->getAlias() , 'type' => SOCIAL_TYPE_GROUP , 'id' => $group->id , 'discussionId' => $discussion->id , 'external' => true ) , false );
		$options[ 'title' ]		= $discussion->title;
		$options[ 'content']	= $reply->getContent();
		$options['discussionId']		= $reply->id;
		$options[ 'userId' ]	= $reply->created_by;
		$options[ 'targets' ]	= $discussion->getParticipants( array( $reply->created_by ) );

		return $group->notifyMembers( 'discussion.reply' , $options );
	}
	
}
