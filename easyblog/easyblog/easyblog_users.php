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
jimport( 'simpleschema.blog.post' );
jimport( 'simpleschema.category' );
jimport( 'simpleschema.person' );

JModelLegacy::addIncludePath(JPATH_SITE.'components/com_api/models');
require_once JPATH_SITE.'/components/com_easyblog/models/users.php';
require_once JPATH_SITE.'/components/com_easyblog/models/blogger.php';
require_once JPATH_SITE.'/components/com_easyblog/helpers/helper.php';
//~ require_once JPATH_SITE.'/plugins/api/easyblog/libraries/simpleschema/bloggers.php';

class EasyblogApiResourceEasyblog_users extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getEasyBlog_user());
	}
	public function post()
	{
	   $this->plugin->setResponse();
	}
	public function getEasyBlog_user()
	{
		$app = JFactory::getApplication();
		$limitstart = $app->input->get('limitstart',0,'INT');
		$limit =  $app->input->get('limit',0,'INT');		
		$search =  $app->input->get('search','','STRING');		
		$ob1  = new EasyBlogModelBlogger();
		$ob1->setState('limitstart',$limitstart);
		$bloggers = $ob1->getAllBloggers('latest',$limit, $filter='showallblogger' , $search );		
		$blogger = EasyBlogHelper::getTable( 'Profile', 'Table' );		
		foreach( $bloggers as $usr )
		{
			$blogger->load($usr->id);
			//$avatar = $blogger->getAvatar();
			$usr->avatar = $blogger->getAvatar();
		}
		
		return $bloggers;
	}	
}
