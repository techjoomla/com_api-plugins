<?php
/**
 * @package    APIplugins
 * @copyright  Copyright (C) 2018 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die('Restricted access');

/**
 * To build page Schema
 *
 * @since  1.0
 */
class PageSimpleSchema
{
	public $id;

	public $title;

	public $alias;

	public $state;

	public $page_type;

	public $cover_position;

	public $creator_name;

	public $friends;

	public $isinvited;

	public $description;

	public $category_id;

	public $category_name;

	public $type;

	public $avatar_large;

	public $member_count;

	public $hits;

	public $created_by;

	public $created_date;

	public $album_count;

	public $isowner;

	public $ismember;

	public $approval_pending;

	public $cover;

	public $more_info;

	public $params;
}
