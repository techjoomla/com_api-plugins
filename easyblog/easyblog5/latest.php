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
*/
require_once( JPATH_ADMINISTRATOR.'/components/com_easyblog/includes'. '/date/date.php' );
require_once( JPATH_ADMINISTRATOR.'/components/com_easyblog/includes'. '/string/string.php' );
require_once( JPATH_ADMINISTRATOR.'/components/com_easyblog/includes'. '/adsense/adsense.php' );
require_once( JPATH_ADMINISTRATOR.'/components/com_easyblog/includes'. '/formatter/formatter.php' );


class EasyblogApiResourceLatest extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );

	}
	
	public function get() {
		
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
	
		//$id = $input->get('id', null, 'INT');
		$id = 0;
		$search = $input->get('search', '', 'STRING');
		$featured = $input->get('featured',0,'INT');
		$tags = $input->get('tags',0,'INT');
		$user_id = $input->get('user_id',0,'INT');
		$limitstart = $input->get('limitstart',0,'INT');
		$limit = $input->get('limit',10,'INT');
		$posts = array();
		// If we have an id try to fetch the user
		$blog 		= EasyBlogHelper::table( 'Blog' );
		$blog->load( $id );
		$modelPT	= EasyBlogHelper::getModel( 'PostTag' );
		
		if($tags)
		{		
			$rows = $model->getTaggedBlogs( $tags );
		}//for get featured blog
		else if($featured)
		{
			$rows = $this->getfeature_Blog();
			$sorting	= $this->plugin->params->get( 'sorting' , 'featured' );
		}//for get users blog
		else if($user_id)
		{	$blogs = EasyBlogHelper::getModel( 'Blog' );
			$rows = $blogs->getBlogsBy('blogger', $user_id, 'latest');
		}
		else
		{	//to get latest blog
			//$sorting	= $this->plugin->params->get( 'sorting' , 'latest' );
			//$rows 	= $model->getBlogsBy( $sorting , '' , $sorting , 0, EBLOG_FILTER_PUBLISHED, $search );
			$rows = $model->getBlogsBy('', '', 'latest', 0, EBLOG_FILTER_PUBLISHED, $search, true, array(), false, false, true, '', '', null, 'listlength', false);
			//$rows = EB::formatter('list', $rows, false);
		}
		$rows = EB::formatter('list', $rows, false);
		//data mapping
		foreach ($rows as $k => $v) 
		{
			//$item = EB::helper( 'simpleschema' )->mapPost($v,'', 100, array('text'));							
			$scm_obj = new EasyBlogSimpleSchema_plg();
			$item = $scm_obj->mapPost($v,'', 100, array('text'));
		
			$item->tags = $modelPT->getBlogTags($item->postid);
			$item->isowner = ( $v->created_by == $this->plugin->get('user')->id )?true:false;
			
			if($v->blogpassword != '')
			{
                $item->ispassword = true;
            }
            else
            {
                $item->ispassword = false;
            }
                
            $item->blogpassword = $v->blogpassword;
            $model			= EasyBlogHelper::getModel( 'Ratings' );
			$ratingValue	= $model->getRatingValues( $item->postid, 'entry');
		 	$item->rate 	= $ratingValue;
		 	$item->isVoted 	= $model->hasVoted($item->postid,'entry',$this->plugin->get('user')->id);
		 	if($item->rate->ratings==0)
			{					
				$item->rate->ratings=-2;
			}
				
			$posts[] = $item;
		}
		$posts = array_slice($posts, $limitstart,$limit);	
		$this->plugin->setResponse( $posts );
	}
	// get feature blog function.
	public function getfeature_Blog()
	{
		$app = JFactory::getApplication();
		$limit = $app->input->get('limit',10,'INT');	
		$categories = $app->input->get('categories','','STRING');	
		$blogss = new	EasyBlogModelBlog();
		$blogss->setState('limit',$limit);
		$res = $blogss->getFeaturedBlog(array(),$limit);
		return $res;	
	}	
	public static function getName() {
		
	}
	
	public static function describe() {
		
	}	
}
