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
 * API class discussionSimpleSchema
 *
 * @since  1.0
 */
class DiscussionSimpleSchema
{
	public $id;

	public $title;

	public $description;

	public $created_date;

	public $created_by;

	public $replies_count;

	public $last_replied;

	public $hits;

	public $lapsed;

	public $replies;
}

/**
 * API class discussionReplySimpleSchema
 *
 * @since  1.0
 */
class DiscussionReplySimpleSchema
{
	public $id;

	public $created_by;

	public $reply;

	public $created_date;

	public $lapsed;
}
