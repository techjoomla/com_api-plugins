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
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/apps.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceDiscussion extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getGroupDiscussion());
	}

	public function post()
	{
	   $this->plugin->setResponse($this->createGroupDiscussion());   
	}
	
	public function delete()
	{
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
	}
	//function use for get friends data
	function getGroupDiscussion()
	{
		//init variable
		$mainframe = JFactory::getApplication();
		
		$group_id = $mainframe->input->get('id',0,'INT');
		$appId = $mainframe->input->get('discussion_id',0,'INT');
		
		$limitstart	= $mainframe->input->get('limitstart',0,'INT');
		$limit	= $mainframe->input->get('limit',10,'INT');		
			
		$wres = new stdClass;
		$valid = 0;

		if(!$group_id)
		{
			$wres->status = 0;
			$wres->message[] = JText::_( 'PLG_API_EASYSOCIAL_EMPTY_GROUP_ID_MESSAGE' );
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
			
			$mapp	= new EasySocialApiMappingHelper();			
			$model	= FD::model( 'Discussions' );

			$discussions_row	= $model->getDiscussions( $group->id , SOCIAL_TYPE_GROUP , $options );			
			if($limitstart)
			{
				$discussions_row = array_slice($discussions_row,$limitstart,$limit);
			}	
						
			$data['data'] = $mapp->mapItem($discussions_row,'discussion',$this->plugin->get('user')->id);
			return( $data );
		}
	}
	
	//function for create new group
	function createGroupDiscussion()
	{
		//init variable
		$mainframe = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		
		// Load the discussion
		$discuss_id 	= $mainframe->input->get('discussion_id',0,'INT');
		$groupId 	= $mainframe->input->get('group_id',0,'INT');
		$wres = new stdClass;
		$discussion = FD::table( 'Discussion' );
		$discussion->load( $discuss_id );
		
		// Get the current logged in user.
		$my		= FD::user($log_user);

		// Get the group
		$group		= FD::group( $groupId );
		
		// Check if the user is allowed to create a discussion
		if( !$group->isMember() )
		{
			$wres->status = 0;
			$wres->message[] = JText::_( 'PLG_API_EASYSOCIAL_CREATE_GD_NOT_ALLOWED_MESSAGE' );
			return $wres;
		}

		// Assign discussion properties
		$discussion->uid 		= $group->id;
		$discussion->type 		= 'group';
		$discussion->title 		= $mainframe->input->get('title',0,'STRING');
		$discussion->content 	= $mainframe->input->get('content',0,'RAW');
		
		//format content
		$discussion->content = str_replace('<p>','',$discussion->content);
		$discussion->content = str_replace('</p>','',$discussion->content);
		$discussion->content = str_replace('<','[',$discussion->content);
		$discussion->content = str_replace('>',']',$discussion->content);
				
				
				
		// If discussion is edited, we don't want to modify the following items
		if( !$discussion->id )
		{
			$discussion->created_by = $my->id;
			$discussion->parent_id 	= 0;
			$discussion->hits 		= 0;
			$discussion->state 		= SOCIAL_STATE_PUBLISHED;
			$discussion->votes 		= 0;
			$discussion->lock 		= false;
		}

		//$app = $this->getApp();
		$app = FD::table('App');
		$app->load(25);

		// Ensure that the title is valid
		if (!$discussion->title) {
			
			$wres->status = 0;
			$wres->message[] = JText::_( 'PLG_API_EASYSOCIAL_EMPTY_DISCUSSION_TITLE_MESSAGE' );
			return $wres;
		}

		// Lock the discussion
		$state 	= $discussion->store();
		if( !$state )
		{
			$wres->status = 0;
			$wres->message[] = JText::_( 'PLG_API_EASYSOCIAL_UNABLE_CREATE_DISCUSSION_MESSAGE' );
			return $wres;
		}

		// Process any files that needs to be created.
		$discussion->mapFiles();

		// If it is a new discussion, we want to run some other stuffs here.
		if( !$discuss_id && $state)
		{
			// @points: groups.discussion.create
			// Add points to the user that updated the group
			$points = FD::points();
			$points->assign( 'groups.discussion.create' , 'com_easysocial' , $my->id );

			// Create a new stream item for this discussion
			$stream = FD::stream();

			// Get the stream template
			$tpl		= $stream->getTemplate();

			// Someone just joined the group
			$tpl->setActor( $my->id , SOCIAL_TYPE_USER );

			// Set the context
			$tpl->setContext( $discussion->id , 'discussions' );

			// Set the cluster
			$tpl->setCluster( $group->id , SOCIAL_TYPE_GROUP, $group->type );

			// Set the verb
			$tpl->setVerb( 'create' );

			// Set the params to cache the group data
			$registry 	= FD::registry();
			$registry->set( 'group' 	, $group );
			$registry->set( 'discussion', $discussion );

			$tpl->setParams( $registry );

			$tpl->setAccess('core.view');

			// Add the stream
			//$stream->add( $tpl );
			// Send notification to group members only if it is new discussion
			
			$options 	= array();
			$options[ 'permalink' ]	= FRoute::apps( array( 'layout' => 'canvas' , 'customView' => 'item' , 'uid' => $group->getAlias() , 'type' => SOCIAL_TYPE_GROUP , 'id' => $app->getAlias() , 'discussionId' => $discussion->id , 'external' => true ) , false );
			$options['discussionId']		= $discussion->id;
			$options[ 'discussionTitle' ]	= $discussion->title;
			$options[ 'discussionContent']	= $discussion->getContent();
			$options[ 'userId' ]			= $discussion->created_by;

			$group->notifyMembers( 'discussion.create' , $options );
		}
		
		$wres->id = $discussion->id;
		$wres->message[] = JText::_( 'PLG_API_EASYSOCIAL_GROUP_DISCUSSION_CREATED_MESSAGE' );
		return $wres;
	}	
}
