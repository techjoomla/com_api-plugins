<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die( 'Restricted access' );
jimport('joomla.user.user');

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

class EasyblogApiResourceRating extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse("unsupported method,please use post method");
	}
	public function post()
	{
		$this->plugin->setResponse($this->setRatings());
	}	
	public function setRatings()
	{
		 $input = JFactory::getApplication()->input;
		 $user_id = $input->get('uid',0,'INT');		
		 $blog_id = $input->get('blogid',0,'INT');		 	 
		 $values = $input->get('values',0,'INT');		 	 
		 $tabel	= EasyBlogHelper::getTable( 'Ratings' );		 
		 $tabel->uid = $blog_id;
		 $tabel->created_by = $user_id;
		 $tabel->value = $values;
		 $tabel->type = 'entry';
		 //before store ratings need to fill the stars
		 $tabel->fill($user_id,$blog_id,'entry');
		 //rating star will be stored now.		 
		 $ratingValue = $tabel->store();
		 return $ratingValue;
	}	
}
