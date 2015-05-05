<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');

class discussionSimpleSchema {

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

class discussionReplySimpleSchema {

	public $id;
	public $created_by;
	public $reply;
	public $created_date;
	public $lapsed;

}
