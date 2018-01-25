<?php
/**
 * @package    APIplugins
 * @copyright  Copyright (C) 2018 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

/**
 * To build page profile Simple Schema
 *
 * @since  1.0
 */
class ProfileSimpleSchema
{
	public $id;

	public $title;

	public $raw_content;

	public $content;

	public $actor;

	public $published;

	public $last_replied;

	public $likes;

	public $comment_element;

	public $comments;
}

/**
 * To build page field Simple Schema
 *
 * @since  1.0
 */
class FildsSimpleSchema
{
	public $title;

	public $unique_key;

	public $field_id;

	public $field_name;

	public $field_value;

	public $step;

	public $params;
}
