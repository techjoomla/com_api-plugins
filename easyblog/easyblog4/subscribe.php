<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');
jimport('joomla.application.component.helper');
jimport( 'simpleschema.easyblog.blog.post' );
jimport( 'simpleschema.easyblog.category' );
jimport( 'simpleschema.easyblog.person' );

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

require_once JPATH_SITE.'/components/com_easyblog/models/subscription.php';
require_once JPATH_SITE.'/components/com_easyblog/models/blog.php';
require_once JPATH_SITE.'/components/com_easyblog/models/category.php';
//require_once JPATH_SITE.'/components/com_easyblog/tables/blog.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easyblog/models/subscriptions.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easyblog/models/categories.php';

class EasyblogApiResourceSubscribe extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getSubscribers());
	}
	public function post()
	{
		$this->plugin->setResponse($this->addSubscription());
	}
	//common function for getting subscribers by forking other functions by their type.  	
	public function getSubscribers()
	{
		$app = JFactory::getApplication();	
		$type = $app->input->get('type','','STRING');		
		switch($type)
		{
			case 'site': $res1=$this->getSitesubscribers();	
						 return $res1;	
			break;
			case 'blog': $res2=$this->getBlogsubscribers();
						 return $res2;			
			break;
			case 'cat': $res3=$this->getCatsubscribes();
						 return $res3;	
			break;
		}
	}
	
	//getting all site subscribers
	public function getSitesubscribers()
	{	
		$ssmodel = new EasyBlogModelSubscriptions();
		$result['count']= $ssmodel->getTotal();		
		$smodel = new EasyBlogModelSubscription();
		$result['data'] = $smodel->getSiteSubscribers();
		return $result;		
	}
		
	//getting particular blog subscribers
	public function getBlogsubscribers()
	{
		 $app = JFactory::getApplication();		
		$blogid = $app->input->get('blogid',0,'INT');		
		$db		= EasyBlogHelper::db();
		$where	= array();		
	    //Making query for getting count of blog subscription.	
		$query	= 'select count(1) from `#__easyblog_post_subscription` as a';		
        $query  .= ' where  a.post_id = '.$db->Quote($blogid);		
		$db->setQuery( $query );
		$val	= $db->loadResult();
		$result['count'] = $val;		
		$btable = EasyBlogHelper::getTable('Blog');
		//try to save blog id in table		
		$btable->load($blogid);
		$result['data'] = $btable->getSubscribers(array());
		 return $result;
		 
		 
	}		
	//getting particular category subscribers
	public function getCatsubscribes()
	{
		$app = JFactory::getApplication();		
		$catid = $app->input->get('catid',0,'INT');	
		$db		= EasyBlogHelper::db();
		$where	= array();		
	//Making query for getting count of category subscription.	
		$query	= 'select count(1) from `#__easyblog_category_subscription` as a';		
        $query  .= ' where  a.category_id = '.$db->Quote($catid);
		
		$db->setQuery( $query );
		$val	= $db->loadResult();			  	
		$result['count'] = $val;		
		$cmodel = new EasyBlogModelCategory();
		$result['data'] = $cmodel->getCategorySubscribers($catid);
		 return $result;
	}	
	//common function for adding subscribers by forking other functions by their type. 
	public function addSubscription()
	{	
		$app = JFactory::getApplication();	
		$type = $app->input->get('type','','STRING');		
		switch($type)
		{
			case 'site': $res1=$this->addToSitesubscribe();	
						 return $res1;	
			break;
			case 'blog': $res2=$this->addToBlogsubscribe();
						 return $res2;			
			break;
			case 'cat': $res3=$this->addToCategorysubscribe();
						 return $res3;	
			break;
		}
	}
	//function for add user to as site subscriber	
	public function addToSitesubscribe()
	{
		$app = JFactory::getApplication();
		$email = $app->input->get('email','','STRING');
		$userid = $app->input->get('userid','','STRING');
		$name = $app->input->get('name','','STRING');
		$smodel = new EasyBlogModelSubscription();
		$status = $smodel->isSiteSubscribedEmail($email);
		if(!$status)
		{		
			$result = $smodel->addSiteSubscription($email,$userid,$name);
		}
		 else
			return false;
		return $result;		
	}
	//function for add user to as blog subscriber	
	public function addToBlogsubscribe()
	{
		$app 	= JFactory::getApplication();
		$email  = $app->input->get('email','','STRING');
		$userid = $app->input->get('userid','','STRING');
		$name   = $app->input->get('name','','STRING');
		$blogid = $app->input->get('blogid',0,'INT');
		$res = new stdClass;
		$bmodel = new EasyBlogModelBlog();
		$status = $bmodel->isBlogSubscribedUser($blogid,$userid,$email);
		if(!$status)
		{
			$result = $bmodel->addBlogSubscription($blogid,$email,$userid,$name);
			$res->status = 1;	
			$res->message=JText::_( 'PLG_API_EASYBLOG_SUBSCRIPTION_SUCCESS' );		
		
		}
		else
		{
			$res->status = 0;	
			$res->message=JText::_( 'PLG_API_EASYBLOG_ALREADY_SUBSCRIBED' );
			return $res;			 
		}		
		return $res;	
	}
	
	//function for add user to as category subscriber	
	public function addToCategorysubscribe()
	{
		$app 	= JFactory::getApplication();
		$email  = $app->input->get('email','','STRING');
		$userid = $app->input->get('userid','','STRING');
		$name   = $app->input->get('name','','STRING');
		$catid  = $app->input->get('catid',0,'INT');
		$cmodel = new EasyBlogModelCategory();
		$status = $cmodel->isCategorySubscribedUser($catid,$userid,$email);
		if(!$status)
		{
			$result = $cmodel->addCategorySubscription($catid,$email,$userid,$name);
		}
		else
			return false; 		
		return $result;
	}			
}
