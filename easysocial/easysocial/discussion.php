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
			
			$mapp = new EasySocialApiMappingHelper();
			
			$model 			= FD::model( 'Discussions' );
			$discussions_row	= $model->getDiscussions( $group->id , SOCIAL_TYPE_GROUP , $options );
			
			$data['data'] = $mapp->mapItem($discussions_row,'discussion',$this->plugin->get('user')->id);
			//
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
		/*if( !$group->isMember() )
		{
			//error code add here
			return false;
		}*/

		// Assign discussion properties
		$discussion->uid 		= $group->id;
		$discussion->type 		= 'group';
		$discussion->title 		= $mainframe->input->get('title',0,'STRING');
		$discussion->content 	= $mainframe->input->get('content',0,'STRING');

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
			$wres->message[] = 'Discussion title is empty';
			return $wres;
		}

		// Lock the discussion
		$state 	= $discussion->store();

		if( !$state )
		{
			$wres->status = 0;
			$wres->message[] = 'Unable to create discussion,check params';
			return $wres;

		}

		// Process any files that needs to be created.
		$discussion->mapFiles();

		// Get the app
		//$app 	= $this->getApp();

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
			$stream->add( $tpl );

			// Set info message
			//FD::info()->set(false, JText::_( 'APP_GROUP_DISCUSSIONS_DISCUSSION_CREATED_SUCCESS' ), SOCIAL_MSG_SUCCESS );

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
		$wres->message[] = 'Group discussion created';
		return $wres;
		
	}
	
/*
	//format friends object into required object
	function baseGrpObj($node=null)
	{
		if($node==null)
		return 0;

		$user = JFactory::getUser($this->plugin->get('user')->id);

		$list = array();
		
		$grp_obj = FD::model('Groups');
	
		$obj = new stdclass;
		$obj->id = $node->id;
		$obj->title = $node->title;
		$obj->description = $node->description;
		$obj->hits = $node->hits;
		$obj->state = $node->state;
		$obj->created_date = $node->created;
		
		//get category name
		$category 	= FD::table('GroupCategory');
		$category->load($node->category_id);
		$obj->category_id = $node->category_id;
		$obj->category_name = $category->get('title');
		
		$obj->created_by = $node->creator_uid;
		$obj->creator_name = JFactory::getUser($node->creator_uid)->username;
		$obj->type = ($node->type == 1 )?'Private':'Public';
		
		foreach($node->avatars As $ky=>$avt)
		{
			$avt_key = 'avatar_'.$ky;
			$obj->$avt_key = JURI::root().'media/com_easysocial/avatars/group/'.$node->id.'/'.$avt;
		}
		
		//$obj->members = $node->members;
		$obj->member_count = $grp_obj->getTotalMembers($node->id);
		//$obj->cover = $grp_obj->getMeta($node->id);
		
		$alb_model = FD::model('Albums');
		
		$uid = $node->id.':'.$node->title;

		$filters = array('uid'=>$uid,'type'=>'group');
		//get total album count
		$obj->album_count = $alb_model->getTotalAlbums($filters);

		//get group album list
		//$albums = $alb_model->getAlbums($uid,'group');
		
		$obj->isowner = ( $node->creator_uid == $userid )?true:false;
		$obj->ismember = in_array( $log_user->id,$node->members );
		$obj->approval_pending = in_array( $log_user->id,$node->pending );

		/*$news_obj = new EasySocialModelGroups();
		$news = $news_obj->getNews($node->id); */
		
		/*return $obj;
	}
*/
	
}
