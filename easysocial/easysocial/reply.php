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

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/router.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/apps.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
/**
 * API class EasysocialApiResourceReply
 *
 * @since  1.0
 */
class EasysocialApiResourceReply extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->getDiscussionReply();
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->postDiscussionReply();
	}

	/**
	 * Method function use for get friends data
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */

	public function getDiscussionReply()
	{
		// Init variable
		$mainframe		=	JFactory::getApplication();
		$group_id		=	$mainframe->input->get('group_id', 0, 'INT');
		$discussId		=	$mainframe->input->get('discussion_id', 0, 'INT');
		$limit			=	$mainframe->input->get('limit', 10, 'INT');
		$limitstart		=	$mainframe->input->get('limitstart', 0, 'INT');
		$valid			=	0;
		$log_user = JFactory::getUser($this->plugin->get('user')->id);

		// Response object
		$res = new stdclass;
		$res->empty_message = '';

		$model = FD::model('Groups');
		$is_member = $model->isMember($log_user->id, $group_id);

		if (!$group_id)
		{
			$res->empty_message	=	JText::_('PLG_API_EASYSOCIAL_EMPTY_GROUP_ID_MESSAGE');
			$res->result = array();
			$this->plugin->setResponse($res);
		}
		elseif (!$is_member)
		{
			$res->empty_message = JText::_('COM_EASYSOCIAL_GROUPS_CLOSED_GROUP_INFO');
			$res->result = array();
			$this->plugin->setResponse($res);
		}
		else
		{
			$group		= FD::group($group_id);

			// Get the current filter type
			$filter		=	$mainframe->input->get('filter', 'all', 'STRING');
			$options 	=	array();

			if ($filter == 'unanswered')
			{
				$options['unanswered']	=	true;
			}

			if ($filter == 'locked')
			{
				$options['locked']	=	true;
			}

			if ($filter == 'resolved')
			{
				$options['resolved']	=	true;
			}

			$options['ordering']	=	'created';
			$mapp					=	new EasySocialApiMappingHelper;
			$model					=	FD::model('Discussions');
			$reply_rows				=	$model->getReplies($discussId, $options);

			if ($discussId)
			{
				$disc_dt	=	new stdClass;

				// Create discussion details as per request
				$discussion			=	FD::table('Discussion');
				$discussion->load($discussId);
				$data_node[]		=	$discussion;
				$res->result->discussion	=	$mapp->mapItem($data_node, 'discussion', $this->plugin->get('user')->id);
			}

			if ($limitstart)
			{
				$reply_rows	=	array_slice($reply_rows, $limitstart, $limit);
			}

			$res->result->replies = $mapp->mapItem($reply_rows, 'reply', $this->plugin->get('user')->id);
			$this->plugin->setResponse($res);
		}
	}

	/**
	 * Method function for create new group
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function postDiscussionReply()
	{
		// Init variable
		$mainframe	=	JFactory::getApplication();
		$log_user	=	$this->plugin->get('user')->id;

		// Load the discussion
		$discuss_id		=	$mainframe->input->get('discussion_id', 0, 'INT');
		$groupId		=	$mainframe->input->get('group_id', 0, 'INT');
		$content		=	$mainframe->input->get('content', '', 'RAW');

		/*$content		=	str_replace('<p>', '', $content);
		$content		=	str_replace('</p>', '', $content);
		$content		=	str_replace('<', '[', $content);
		$content		=	str_replace('>', ']', $content);*/

		$content	=	str_replace('<p>', '', $content);
		$content	=	str_replace('</p>', '', $content);
		$content	=	str_replace('<', '[', $content);
		$content	=	str_replace('>', ']', $content);
		$content	=	str_replace('strong', 'b', $content);
		$content	=	str_replace('em', 'i', $content);
		$content	=	str_replace('nbsp;', ' ', $content);

		$res			=	new stdClass;
		$discussion		=	FD::table('Discussion');
		$discussion->load($discuss_id);

		// Get the current logged in user.
		$my				=	FD::user($log_user);

		// Get the group
		$group				=	FD::group($groupId);
		$reply 				=	FD::table('Discussion');
		$reply->uid 		=	$discussion->uid;
		$reply->type 		=	$discussion->type;
		$reply->content 	=	$content;
		$reply->created_by	=	$log_user;
		$reply->parent_id	=	$discussion->id;
		$reply->state		=	SOCIAL_STATE_PUBLISHED;

		// Save the reply.
		$state	=	$reply->store();

		if ($state)
		{
			$this->createStream($discussion, $group, $reply, $log_user);
			$res->result->id			=	$discussion->id;
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_DISCUSSION_REPLY_MESSAGE');

			$this->plugin->setResponse($res);
		}
	}

	/**
	 * Method createStream
	 *
	 * @param   int     $discussion  discussion id
	 * @param   int     $group       group id
	 * @param   string  $reply       reply
	 * @param   int     $log_user    user id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function createStream($discussion, $group, $reply, $log_user)
	{
		// Create a new stream item for this discussion
		$stream	=	FD::stream();
		$my		= FD::user($log_user);

		// Get the stream template
		$tpl	= $stream->getTemplate();

		// Someone just joined the group
		$tpl->setActor($log_user, SOCIAL_TYPE_USER);

		// Set the context
		$tpl->setContext($discussion->id, 'discussions');

		// Set the cluster
		$tpl->setCluster($group->id, SOCIAL_TYPE_GROUP, $group->type);

		// Set the verb
		$tpl->setVerb('reply');

		// Set the params to cache the group data
		$registry	=	FD::registry();
		$registry->set('group', $group);
		$registry->set('reply', $reply);
		$registry->set('discussion', $discussion);

		$tpl->setParams($registry);

		$tpl->setAccess('core.view');

		// Add the stream
		$stream->add($tpl);

		// Update the parent's reply counter.
		$discussion->sync($reply);

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

		$options 	=	array();
		$options['permalink']	=	FRoute::apps(
													array(
														'layout' => 'canvas',
														'customView' => 'item',
														'uid' => $group->getAlias(),
														'type' => SOCIAL_TYPE_GROUP,
														'id' => $group->id,
														'discussionId' => $discussion->id,
														'external' => true
													),
													false
												);
		$options['title']			=	$discussion->title;
		$options['content']			=	$reply->getContent();
		$options['discussionId']	=	$reply->id;
		$options['userId']		=	$reply->created_by;
		$options['targets']		=	$discussion->getParticipants(array($reply->created_by));

		return $group->notifyMembers('discussion.reply', $options);
	}
}
