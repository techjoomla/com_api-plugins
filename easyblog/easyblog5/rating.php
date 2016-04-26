<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die( 'Restricted access' );
jimport('joomla.user.user');
jimport( 'simpleschema.easyblog.category' );
jimport( 'simpleschema.easyblog.person' );
jimport( 'simpleschema.easyblog.blog.post' );

/*
require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );
//for image upload
require_once( EBLOG_CLASSES . '/mediamanager.php' );
require_once( EBLOG_HELPERS . '/image.php' );
require_once( EBLOG_CLASSES . '/easysimpleimage.php' );
require_once( EBLOG_CLASSES . '/mediamanager/local.php' );
require_once( EBLOG_CLASSES . '/mediamanager/types/image.php' );
*/
class EasyblogApiResourceRating extends ApiResource
{
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
		 $model			= EasyBlogHelper::table( 'Ratings' );		 
		 $model->uid = $blog_id;
		 $model->created_by = $user_id;
		 $model->value = $values;
		 $model->type = 'entry';
		 //$model->fill($user_id,$blog_id,'entry');		 
		 $ratingValue = $model->store();
		 return $ratingValue;
	}	
}
