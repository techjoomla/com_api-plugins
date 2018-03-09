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

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/apps.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/** Discussion API 
 * 
 * @since  1.8.8
 */
class EasysocialApiResourceDiscussion extends ApiResource
{
	/** Get
	 * 
	 * @return	Array	Discussions
	 */
	public function get()
	{
		$this->getGroupDiscussion();
	}

	/** POST
	 * 
	 * @return	String	Message
	 */
	public function post()
	{
		$this->createGroupDiscussion();
	}

	/** DELETE
	 * 
	 * @return	object|boolean	in success object will return, in failure boolean
	 */
	public function delete()
	{
		$result		=	new stdClass;
		$app	=	JFactory::getApplication();
		$group_id	=	$app->input->get('id', 0, 'INT');
		$appId		=	$app->input->get('discussion_id', 0, 'INT');
		$discussion	=	ES::table('Discussion');
		$discussion->load($appId);
		$my 		=	ES::user();
		$group 		=	ES::group($group_id);

		if (!$group->isAdmin() && $discussion->created_by != $this->plugin->get('user')->id)
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_NOT_ALLOW_DELETE_DISCUSSION'));
		}

		// Delete the discussion
		$res			=	$discussion->delete();

		if ($res)
		{
			$result->status	=	JText::_('PLG_API_EASYSOCIAL_CONVERSATION_DELETED_MESSAGE');
		}
		else
		{
			$result->status	=	JText::_('PLG_API_EASYSOCIAL_CONVERSATION_UNABLE_DELETED_MESSAGE');
		}

		$this->plugin->setResponse($result);
	}

	/** getGroupDiscussion
	 * Function use for get friends data
	 * 
	 * @return	object|boolean	in success object will return, in failure boolean
	 */
	private function getGroupDiscussion()
	{
		// Init variable
		$mainframe		=	JFactory::getApplication();
		$clusterId		=	$mainframe->input->get('id', 0, 'INT');
		$type			=	$mainframe->input->get('type', 'group', 'string');
		$appId			=	$mainframe->input->get('discussion_id', 0, 'INT');
		$limitstart		=	$mainframe->input->get('limitstart', 0, 'INT');
		$limit			=	$mainframe->input->get('limit', 10, 'INT');
		$valid			=	0;

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		if (!$clusterId)
		{
			$res->empty_message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_ID');

			$this->plugin->setResponse($res);
		}
		else
		{
			if ($type == 'group')
			{
				$cluster		= ES::group($clusterId);
				$cluster_type   = SOCIAL_TYPE_GROUP;
			}
			else
			{
				$cluster		= ES::page($clusterId);
				$cluster_type   = SOCIAL_TYPE_PAGE;
			}

			// Get the current filter type
			$filter		=	$mainframe->input->get('filter', 'all', 'STRING');
			$options	=	array();

			if ($filter == 'unanswered')
			{
				$options['unanswered']		=	true;
			}

			if ($filter == 'locked')
			{
				$options['locked']			=	true;
			}

			if ($filter == 'resolved')
			{
				$options['resolved']		=	true;
			}

			$mapp	=	new EasySocialApiMappingHelper;
			$model	=	ES::model('Discussions');

			$discussions_row	=	$model->getDiscussions($cluster->id, $cluster_type, $options);

			if (count($discussions_row))
			{
				if ($limitstart)
				{
					$discussions_row	=	array_slice($discussions_row, $limitstart, $limit);
				}

				$res->result		=	$mapp->mapItem($discussions_row, 'discussion', $this->plugin->get('user')->id);
			}
			else
			{
				$res->empty_message	=	JText::_('PLG_API_EASYSOCIAL_NO_DISCUSSION_FOUNDS');
			}

			$this->plugin->setResponse($res);
		}
	}

	/** getGroupDiscussion
	 * Function for create new group
	 * 
	 * @return	object|boolean	in success object will return, in failure boolean
	 */

	private function createGroupDiscussion()
	{
		// Init variable
		$mainframe		=	JFactory::getApplication();
		$log_user		=	$this->plugin->get('user')->id;

		// Load the discussion
		$discuss_id		=	$mainframe->input->get('discussion_id', 0, 'INT');
		$groupId		=	$mainframe->input->get('group_id', 0, 'INT');
		$res			=	new stdClass;
		$discussion		=	ES::table('Discussion');
		$discussion->load($discuss_id);

		// Get the current logged in user.
		$my				=	ES::user($log_user);

		// Get the group
		$group			=	ES::group($groupId);

		// Check if the user is allowed to create a discussion
		if (!$group->isMember())
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_CREATE_GD_NOT_ALLOWED_MESSAGE'));
		}

		// Assign discussion properties
		$discussion->uid 		=	$group->id;
		$discussion->type 		=	'group';
		$discussion->title 		=	$mainframe->input->get('title', 0, 'STRING');
		$discussion->content 	=	$mainframe->input->get('content', 0, 'RAW');

		// Format content
		$discussion->content	=	str_replace('<p>', '', $discussion->content);
		$discussion->content	=	str_replace('</p>', '', $discussion->content);
		$discussion->content	=	str_replace('<', '[', $discussion->content);
		$discussion->content	=	str_replace('>', ']', $discussion->content);
		$discussion->content	=	str_replace('strong', 'b', $discussion->content);
		$discussion->content	=	str_replace('em', 'i', $discussion->content);
		$discussion->content	=	str_replace('nbsp;', ' ', $discussion->content);

		// If discussion is edited, we don't want to modify the following items

		if (!$discussion->id)
		{
			$discussion->created_by		=	$my->id;
			$discussion->parent_id		=	0;
			$discussion->hits			=	0;
			$discussion->state			=	SOCIAL_STATE_PUBLISHED;
			$discussion->votes			=	0;
			$discussion->lock			=	false;
		}

		// $app = $this->getApp();
		$app	=	ES::table('App');
		$app->load(25);

		// Ensure that the title is valid

		if (!$discussion->title)
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_EMPTY_DISCUSSION_TITLE_MESSAGE'));
		}

		// Lock the discussion
		$state	=	$discussion->store();

		if (!$state)
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_UNABLE_CREATE_DISCUSSION_MESSAGE'));
		}

		// Process any files that needs to be created.
		$discussion->mapFiles();

		// If it is a new discussion, we want to run some other stuffs here.
		if (!$discuss_id && $state)
		{
			// @points: groups.discussion.create
			// Add points to the user that updated the group
			$points		=	ES::points();
			$points->assign('groups.discussion.create', 'com_easysocial', $my->id);

			// Create a new stream item for this discussion
			$stream		=	ES::stream();

			// Get the stream template
			$tpl		=	$stream->getTemplate();

			// Someone just joined the group
			$tpl->setActor($my->id, SOCIAL_TYPE_USER);

			// Set the context
			$tpl->setContext($discussion->id, 'discussions');

			// Set the cluster
			$tpl->setCluster($group->id, SOCIAL_TYPE_GROUP, $group->type);

			// Set the verb
			$tpl->setVerb('create');

			// Set the params to cache the group data
			$registry	=	ES::registry();
			$registry->set('group', $group);
			$registry->set('discussion', $discussion);

			$tpl->setParams($registry);

			$tpl->setAccess('core.view');

			/* Add the stream
			 * $stream->add( $tpl );
			 *Send notification to group members only if it is new discussion
			*/
			$options						=	array();
			$options['permalink']			=	FRoute::apps(
																array(
																	'layout' => 'canvas',
																	'customView' => 'item',
																	'uid' => $group->getAlias(),
																	'type' => SOCIAL_TYPE_GROUP,
																	'id' => $app->getAlias(),
																	'discussionId' => $discussion->id,
																	'external' => true
																),
													false
												);
			$options['discussionId']		=	$discussion->id;
			$options['discussionTitle']		=	$discussion->title;
			$options['discussionContent']	= $discussion->getContent();
			$options['userId']			= $discussion->created_by;

			$group->notifyMembers('discussion.create', $options);
		}

		$res->result->id			=	$discussion->id;
		$res->result->status		=	1;
		$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_GROUP_DISCUSSION_CREATED_MESSAGE');

		$this->plugin->setResponse($res);
	}
}
