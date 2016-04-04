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

//for image upload
require_once( EBLOG_ADMIN_INCLUDES . '/mediamanager/mediamanager.php' );
require_once( EBLOG_ADMIN_INCLUDES . '/blogimage/blogimage.php' );
require_once( EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/local.php' );
require_once( EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/post.php' );
require_once( EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/posts.php' );
require_once( EBLOG_ADMIN_INCLUDES . '/mediamanager/adapters/abstract.php' );

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
		$blog 		= EasyBlogHelper::table( 'Blog' );
		$data = $input->post->getArray(array());
		$log_user = $this->plugin->get('user')->id;
		$createTag = array();
		$res = new stdClass;

		/*		
		//format date as per easyblog
		$date = EB::date($data['created'], true);
		$data['created'] = $date->toSQL(true);
		
		$date = EB::date($data['publish_up'], true);
		$data['publish_up'] = $date->toSQL(true);
		//
		*/
		//to create revision and uid
		$post = EB::post(NULL);
		$post->create();
		$key = 'post:'.$post->id;
		
		$file = JRequest::getVar( 'file' , '' , 'FILES' , 'array' );
		
		$data['image'] = '';
		
		if($file['name'])
		{
		  $image_obj = $this->uploadImage($key);
		  $data['image'] = $image_obj->media->uri; 
		}
		//document needs to get from app
		$data['content'] = $input->get('content', '', 'raw');
		$data['document'] = null;
		$data['published'] = $input->get('published', 1, 'INT');
		$data['created_by'] = $log_user;
		
		$uid = $post->uid;
		// Load up the post library
		$post = EB::post($uid);
		$post->bind($data, array());

		// Default options
		$options = array();

		// since this is a form submit and we knwo the date that submited already with the offset timezone. we need to reverse it.
		$options['applyDateOffset'] = true;

		// check if this is a 'Apply' action or not.
		$isApply = $input->post->get('isapply', false, 'bool');

		// For autosave requests we do not want to run validation on it.
		$autosave = $input->post->get('autosave', false, 'bool');

		if ($autosave) {
			$options['validateData'] = false;
		}

		// Save post
		try {
			$post->save($options);
		} catch(EasyBlogException $exception) {

			$this->plugin->setResponse( $this->getErrorResponse(404, $blog->getError()) );
			return;
		}

		$bpost = EB::post($post->id);
		// $post->bind($row);
		$item = EB::formatter('entry', $bpost);
		$scm_obj = new EasyBlogSimpleSchema_plg();
		$item = $scm_obj->mapPost($item, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');

		$this->plugin->setResponse( $item );
   	   
	}
	
	//get blog details 
	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$config = EasyBlogHelper::getConfig();
		$id = $input->get('id', null, 'INT');

		// If we have an id try to fetch the user
		$blog = EasyBlogHelper::table( 'Blog' );
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
		//format data for get image using function
		$post = EB::post($blog->id);
		// $post->bind($row);
		$post = EB::formatter('entry', $post);
		$scm_obj = new EasyBlogSimpleSchema_plg();
		$item = $scm_obj->mapPost($post, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');
		//$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($blog, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');
		$item->isowner = ( $blog->created_by == $this->plugin->get('user')->id )?true:false;
		$item->allowcomment = $blog->allowcomment;

        $item->allowsubscribe = $blog->subscription;

		// Tags
		$modelPT	= EasyBlogHelper::getModel( 'PostTag' );
		$item->tags = $modelPT->getBlogTags($blog->id);
		
		//created by vishal - for show extra images
		//$item->text = preg_replace('/"images/i', '"'.JURI::root().'images', $item->text );
		//$item->text = str_replace('href="','href="'.JURI::root(),$item->text);
		//$item->text = str_replace('src="','src="'.JURI::root(),$item->text);
				
		$this->plugin->setResponse( $item );
	}
	//delet blog
	public function delete_blog()
	{		
		$app = JFactory::getApplication();
		$id = $app->input->get('id',0,'INT');
		$blog = EasyBlogHelper::table( 'Blog', 'Table' );
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
	
	//upload image
	public function uploadImage($key)
	{
		// Load up media manager
		$mm = EB::mediamanager();

		// Get the target folder
		$placeId = EBMM::getUri($key);

		// Get the file input
		$file = JRequest::getVar( 'file' , '' , 'FILES' , 'array' );

		// Check if the file is really allowed to be uploaded to the site.
		$state = EB::image()->canUploadFile($file);

		if ($state instanceof Exception) {
			//add error code
			return $state;
			//return $this->output($state);
		}

		// MM should check if the user really has access to upload to the target folder
		$allowed = EBMM::hasAccess($placeId);

		if ($allowed instanceof Exception) {
			//add error code
			return $state;
			//return $this->output($allowed);
		}

		// Check the image name is it got contain space, if yes need to replace to '-'
		$fileName = $file['name'];
		$file['name'] = str_replace(' ', '-', $fileName);

		// Upload the file now
		$file = $mm->upload($file, $placeId);

		// Response object is intended to also include
		// other properties like status message and status code.
		// Right now it only inclues the media item.
		$response = new stdClass();
		$response->media = EBMM::getMedia($file->uri);
		
		//code for future use
		//header('Content-type: text/x-json; UTF-8');
		//$resp =  json_encode($response, JSON_HEX_TAG);
		return $response;
	}
}
