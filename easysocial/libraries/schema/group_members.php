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
 * API class GroupMembersSimpleSchema
 *
 * @since  1.0
 */
class GroupMembersSimpleSchema
{
	public $id;

	public $username;

	public $image;

	public $isself;

	public $cover;

	public $friend_count;

	public $follower_count;

	public $badges;

	public $points;

	public $more_info;
}
