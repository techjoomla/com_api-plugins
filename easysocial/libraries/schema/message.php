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
 * API class converastionSimpleSchema
 *
 * @since  1.0
 */
class ConverastionSimpleSchema
{
	public $conversion_id;

	public $created_date;

	public $lastreplied_date;

	public $isread;

	public $messages;

	public $lapsed;

	public $participant;
}

/**
 * API class MessageSimpleSchema
 *
 * @since  1.0
 */
class MessageSimpleSchema
{
	public $id;

	public $message;

	public $attachment;

	public $created_date;

	public $created_by;

	public $lapsed;

	public $isself;
}

/**
 * API class ReplySimpleSchema
 *
 * @since  1.0
 */
class ReplySimpleSchema
{
	public $id;

	public $created_by;

	public $reply;

	public $created_date;

	public $lapsed;
}
