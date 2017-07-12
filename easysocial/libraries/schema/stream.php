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

defined('_JEXEC') or die('Restricted access');

/**
 * API class StreamSimpleSchema
 *
 * @since  1.0
 */
class StreamSimpleSchema
{
	public $id;

	public $title;

	public $type;

	public $group;

	public $element_id;

	public $preview;

	public $raw_content_url;

	public $content;

	public $actor;

	public $published;

	public $last_replied;

	public $likes;

	public $comment_element;

	public $comments;

	public $share_url;

	public $stream_url;

	public $with;

	public $isPinned;

	public $file_name;

	public $download_file_url;
}

/**
 * API class LikesSimpleSchema
 *
 * @since  1.0
 */
class LikesSimpleSchema
{
	public $uid;

	public $type;

	public $stream_id;

	public $verb;

	public $created_by;

	public $total;

	// Public $hasliked;
}

/**
 * API class CommentsSimpleSchema
 *
 * @since  1.0
 */
class CommentsSimpleSchema
{
	public $uid;

	public $element;

	public $element_id;

	public $comment;

	public $verb;

	public $group;

	public $stream_id;

	public $created_by;

	public $created;

	public $lapsed;

	public $params;
}
