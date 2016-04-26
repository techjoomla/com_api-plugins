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

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

//for image upload
require_once( EBLOG_CLASSES . '/mediamanager.php' );
require_once( EBLOG_HELPERS . '/image.php' );
require_once( EBLOG_CLASSES . '/easysimpleimage.php' );
require_once( EBLOG_CLASSES . '/mediamanager/local.php' );
require_once( EBLOG_CLASSES . '/mediamanager/types/image.php' );

class EasyblogApiResourceBlog extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );
	}
	public function delete()
	{
	$this->plugin->setResponse($this->delete_blog());
	}
	public function post()
	{    	
		$input = JFactory::getApplication()->input;
		$blog = EasyBlogHelper::getTable( 'Blog', 'Table' );
		$post = $input->post->getArray(array());
		$log_user = $this->plugin->get('user')->id;
		$createTag = array();
		$res = new stdClass;
	
		//code for upload
		$blog->bind($post,true);

		$blog->permalink = str_replace('+','-',$blog->title);
		//for publish unpublish blog.
		$blog->published = $post['published'];
		//create tags for blog
		$createTag = $post['tags'];
		
		//$blog->write_content = 1;
		//$blog->write_content_hidden = 1;
		
		$blog->created_by = $log_user;

		$blog->created = date("Y-m-d h:i:s");
		$blog->publish_up = date("Y-m-d h:i:s");
		
		//$blog->created = EasyBlogHelper::getDate();
		//get date from app
		//$blog->publish_up = EasyBlogHelper::getDate();
		
		$blog->created_by = $this->plugin->getUser()->id;

			if (!$blog->store()) {
				$this->plugin->setResponse( $this->getErrorResponse(404, $blog->getError()) );
				return;
			}
		//create tags for blog called the function	
		$blog->processTags( $createTag, 1 );
		$blog->processTrackbacks();
			//$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($blog, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li>');
			$scm_obj = new EasyBlogSimpleSchema_4();
			$item = $scm_obj->mapPost($v,'', 100, array('text'));

			$this->plugin->setResponse( $item );
   	   
	}
	
	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$config = EasyBlogHelper::getConfig();
		$id = $input->get('id', null, 'INT');

		// If we have an id try to fetch the user
		$blog = EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $id );
		
		if (!$id) 
		{
			$this->plugin->setResponse( $this->getErrorResponse(404, JText::_( 'PLG_API_EASYBLOG_BLOG_ID_MESSAGE' )) );
			return;
		}
				
		if (!$blog->id) 
		{
			$this->plugin->setResponse( $this->getErrorResponse(404, JText::_( 'PLG_API_EASYBLOG_BLOG_NOT_FOUND_MESSAGE' )) );
			return;
		}

		//$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($blog, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');
		$scm_obj = new EasyBlogSimpleSchema_4();
		$item = $scm_obj->mapPost($blog, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');
		
		$item->isowner = ( $blog->created_by == $this->plugin->get('user')->id )?true:false;
		$item->allowcomment = $blog->allowcomment;

        $item->allowsubscribe = $blog->subscription;

		// Tags
		$modelPT	= EasyBlogHelper::getModel( 'PostTag' );
		$item->tags = $modelPT->getBlogTags($blog->id);
		
		//created by vishal - for show extra images
		//$item->text = preg_replace('/"images/i', '"'.JURI::root().'images', $item->text );
		$item->text = str_replace('href="','href="'.JURI::root(),$item->text);
		$item->text = str_replace('src="','src="'.JURI::root(),$item->text);
				
		$this->plugin->setResponse( $item );
	}
	public function delete_blog()
	{		
		$app = JFactory::getApplication();
		$id = $app->input->get('id',0,'INT');
		$blog = EasyBlogHelper::getTable( 'Blog', 'Table' );
		$blog->load( $id );
		if(!$blog->id || !$id)
		{
			$res->status =0;	
			$res->message=JText::_( 'PLG_API_EASYBLOG_BLOG_NOT_EXISTS_MESSAGE' );
			return $res;	
		}
		else
		{
			$val = $blog->delete($id);
			$re->status = $val;
			$res->message=JText::_( 'PLG_API_EASYBLOG_DELETE_MESSAGE' );
			return $res;
		}	
	}	
}
