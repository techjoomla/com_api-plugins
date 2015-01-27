<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.user.user');
jimport( 'simpleschema.category' );
jimport( 'simpleschema.person' );
jimport( 'simpleschema.blog.post' );

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

class EasyblogApiResourceBlog extends ApiResource
{

	public $default_retain_tags = '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe><img>';
	public $disallowed_tags = array('script','style');
	
	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );
	}

	public function post()
	{    	
			$input = JFactory::getApplication()->input;
			$blog = EasyBlogHelper::getTable( 'Blog', 'Table' );
			$post = $input->post->getArray(array());
			
			$blog->bind($post);
			$blog->created_by = $this->plugin->getUser()->id;
			
			if (!$blog->store()) {
				$this->plugin->setResponse( $this->getErrorResponse(404, $blog->getError()) );
				return;
			}
			
			$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($blog, $this->getRetainedTags());
			$this->plugin->setResponse( $item );
   	   
	}
	
	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$config = EasyBlogHelper::getConfig();
		$id = $input->get('id', null, 'INT');

		// If we have an id try to fetch the user
		$blog 		= EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $id );
		
		if (!$id) {
			$this->plugin->setResponse( $this->getErrorResponse(404, 'Blog id cannot be blank') );
			return;
		}
				
		if (!$blog->id) {
			$this->plugin->setResponse( $this->getErrorResponse(404, 'Blog not found') );
			return;
		}

		$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($blog, $this->getRetainedTags());
		$this->plugin->setResponse( $item );
	}
	
	public function getRetainedTags() {
		$plugin = JPluginHelper::getPlugin('api', 'easyblog');
		$params = new JRegistry($plugin->params);
		
		$tags = explode(',', $params->get('retain_tags'));
		$trimmed_array = array_map('trim', $tags);
		$retain_tags = array_diff($trimmed_array, $this->disallowed_tags);
		
		if (!count($retain_tags))
		{
			return $default_retain_tags;
		}
		
		return '<' . implode('><', $retain_tags) . '>';
	}
	
}
