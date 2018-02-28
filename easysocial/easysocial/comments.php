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
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/** Comments API 
 * 
 * @since  1.8.8
 */
class EasysocialApiResourceComments extends ApiResource
{
	/** Get
	 * 
	 * @return	array	comments
	 */
	public function get()
	{
		$this->getComments();
	}

	/** Post
	 * 
	 * @return	String	message
	 */
	public function post()
	{
		$app		=	JFactory::getApplication();
		$element	=	$app->input->get('element', '', 'string');
		$group		=	$app->input->get('group', '', 'string');
		$verb		=	$app->input->get('verb', '', 'string');

		// Element id
		$uid		=	$app->input->get('uid', 0, 'int');
		$input		=	$app->input->get('comment', "", 'RAW');
		$params		=	$app->input->get('params', array(), 'ARRAY');
		$streamid	=	$app->input->get('stream_id', '', 'INT');

		// Parent comment id
		$parent		=	$app->input->get('parent', 0, 'INT');
		$res 		=	new stdClass;

		if (!$uid)
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_EMPTY_ELEMENT_NOT_ALLOWED_MESSAGE'));
		}

		// Determine if this user has the permissions to add comment.
		$access 	= ES::access();
		$allowed	= $access->get('comments.add');

		if (!$allowed)
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_COMMENT_NOT_ALLOW_MESSAGE'));
		}

		if (empty($input))
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_EMPTY_COMMENT_NOT_ALLOWED_MESSAGE'));
		}

			// Normalize CRLF (\r\n) to just LF (\n)
			$input				=	str_ireplace("\r\n", "\n", $input);
			$compositeElement	=	$element . '.' . $group . '.' . $verb;
			$table				=	ES::table('comments');
			$table->element		=	$compositeElement;
			$table->uid			=	$uid;
			$table->comment		=	$input;
			$table->created_by	=	ES::user()->id;
			$table->created		=	ES::date()->toSQL();
			$table->parent		=	$parent;
			$table->params		=	$params;
			$table->stream_id	=	$streamid;
			$state				=	$table->store();

			if ($streamid)
			{
				$doUpdate = true;

				if ($element == 'photos')
				{
					$sModel		=	ES::model('Stream');
					$totalItem	=	$sModel->getStreamItemsCount($streamid);

					if ($totalItem > 1)
					{
							$doUpdate = false;
					}
				}

					if ($doUpdate)
					{
						$stream = ES::stream();
						$stream->updateModified($streamid, ES::user()->id, SOCIAL_STREAM_LAST_ACTION_COMMENT);
					}
			}

			if ($state)
			{
				$dispatcher	=	ES::dispatcher();
				$comments	=	array(&$table);
				$args		=	array(&$comments);

				// @trigger: onPrepareComments
				$dispatcher->trigger($group, 'onPrepareComments', $args);

				// Create result obj
				$res->result->message		=	JText::_('PLG_API_EASYSOCIAL_COMMENT_SAVE_SUCCESS_MESSAGE');
			}
			else
			{
				// Create result obj
				ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_COMMENT_SAVE_UNSUCCESS_MESSAGE'));
			}

		$this->plugin->setResponse($res);
	}

	/** GetComments
	 * 
	 * @return	array	comments
	 */
	private function getComments()
	{
		$app				=	JFactory::getApplication();
		$log_user			=	JFactory::getUser($this->plugin->get('user')->id);
		$row				=	new stdClass;
		$row->uid			=	$app->input->get('uid', 0, 'INT');
		$row->element		=	$app->input->get('element', '', 'STRING');

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		// Discussions.group.reply
		$row->stream_id		=	$app->input->get('stream_id', 0, 'INT');
		$row->group			=	$app->input->get('group', '', 'STRING');
		$row->verb			=	$app->input->get('verb', '', 'STRING');
		$row->limitstart	=	$app->input->get('limitstart', 0, 'INT');
		$row->limit			=	$app->input->get('limit', 10, 'INT');
		$row->userid		=	$log_user->id;
		$mapp				=	new EasySocialApiMappingHelper;
		$data				=	$mapp->createCommentsObj($row, $row->limitstart, $row->limit);

		// Determine if this user has the permissions to read comment.
		$access 	= ES::access();
		$allowed	= $access->get('comments.read');

		if (!$allowed)
		{
			ApiError::raiseError(403, JText::_('PLG_API_EASYSOCIAL_READ_COMMENT_NOT_ALLOW_MESSAGE'));
		}

		if (count($data['data']) < 1)
		{
			$res->empty_message	=	JText::_('APP_USER_KOMENTO_NO_COMMENTS_FOUND');
		}
		else
		{
			$res->result	=	$data;
		}

		$this->plugin->setResponse($res);
	}

	/** Delete
	 * 
	 * @return	string	message
	 */
	public function delete()
	{
		$app			=	JFactory::getApplication();
		$conversion_id	=	$app->input->get('conversation_id', 0, 'INT');
		$result			=	new stdClass;

		if (!$conversion_id)
		{
			ApiError::raiseError(400, JText::_('PLG_API_EASYSOCIAL_INVALID_CONVERSATION_MESSAGE'));
		}

		// Try to delete the group
		$conv_model			=	ES::model('Conversations');
		$conv_model->delete($conversion_id, $this->plugin->get('user')->id);
		$result->message	=	JText::_('PLG_API_EASYSOCIAL_CONVERSATION_DELETED_MESSAGE');

		$this->plugin->setResponse($result);
	}
}
