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

/**
 * API class ProfileSimpleSchema
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
 * API class FildsSimpleSchema
 *
 * @since  1.0
 */
class FildsSimpleSchema
{
	// Public $id;

	public $title;

	public $field_id;

	public $field_name;

	public $field_value;

	public $step;
}
