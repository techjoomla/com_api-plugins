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
		$valid = 1;

		if (!$uid)
		{
			$res->result->id			=	0;
			$res->result->status		=	0;
			$res->result->message	=	JText::_('PLG_API_EASYSOCIAL_EMPTY_ELEMENT_NOT_ALLOWED_MESSAGE');
			$valid				=	0;
		}

		// Message should not be empty.
		if (empty($input))
		{
			$res->result->id			=	0;
			$res->result->status		=	0;
			$res->result->message		=	JText::_('PLG_API_EASYSOCIAL_EMPTY_COMMENT_NOT_ALLOWED_MESSAGE');
			$valid						=	0;
		}
		elseif ($valid)
		{
				// Normalize CRLF (\r\n) to just LF (\n)
				$input				=	str_ireplace("\r\n", "\n", $input);
				$compositeElement	=	$element . '.' . $group . '.' . $verb;
				$table				=	FD::table('comments');
				$table->element		=	$compositeElement;
				$table->uid			=	$uid;
				$table->comment		=	$input;
				$table->created_by	=	FD::user()->id;
				$table->created		=	FD::date()->toSQL();
				$table->parent		=	$parent;
				$table->params		=	$params;
				$table->stream_id	=	$streamid;
				$state				=	$table->store();

				if ($streamid)
				{
					$doUpdate = true;

					if ($element == 'photos')
					{
						$sModel		=	FD::model('Stream');
						$totalItem	=	$sModel->getStreamItemsCount($streamid);

						if ($totalItem > 1)
						{
								$doUpdate = false;
						}
					}

						if ($doUpdate)
						{
							$stream = FD::stream();
							$stream->updateModified($streamid, FD::user()->id, SOCIAL_STREAM_LAST_ACTION_COMMENT);
						}
				}

				if ($state)
				{
					$dispatcher	=	FD::dispatcher();
					$comments	=	array(&$table);
					$args		=	array(&$comments);

					// @trigger: onPrepareComments
					$dispatcher->trigger($group, 'onPrepareComments', $args);

					// Create result obj
					$res->result->status		=	1;
					$res->result->message		=	JText::_('PLG_API_EASYSOCIAL_COMMENT_SAVE_SUCCESS_MESSAGE');
				}
				else
				{
					// Create result obj
					$res->result->status		=	0;
					$res->result->message		=	JText::_('PLG_API_EASYSOCIAL_COMMENT_SAVE_UNSUCCESS_MESSAGE');
				}
		}

		$this->plugin->setResponse($res);
	}

	/** GetComments
	 * 
	 * @return	array	comments
	 */
	public function getComments()
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
		$valid			=	1;
		$result			=	new stdClass;

		if (!$conversion_id)
		{
			$result->status		=	0;
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_INVALID_CONVERSATION_MESSAGE');
			$valid				=	0;
		}

		if ($valid)
		{
			// Try to delete the group
			$conv_model			=	FD::model('Conversations');
			$result->status		=	$conv_model->delete($conversion_id, $this->plugin->get('user')->id);
			$result->message	=	JText::_('PLG_API_EASYSOCIAL_CONVERSATION_DELETED_MESSAGE');
		}

		$this->plugin->setResponse($result);
	}
}
