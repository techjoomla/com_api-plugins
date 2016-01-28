<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');

class EventsSimpleSchema {
public $id;
public $title;
public $description;
public $params;
public $details;
public $guests;
public $featured;
public $created;
public $categoryId;
public $start_date;
public $end_date;
public $start_date_unix;
public $end_date_unix;
public $category_name;
public $isAttending;
public $isOwner;
public $isMaybe;
public $location;
public $longitude;
public $latitude;
public $cover_image;
public $start_date_ios;
public $end_date_ios;
public $isoverevent;
public $share_url;
public $isPendingMember;	
public $isRecurring;                                
public $hasRecurring;
}
