<?php
defined('_JEXEC') or die( 'Restricted access' );
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

jimport('joomla.user.user');
jimport( 'simpleschema.category' );
jimport( 'simpleschema.person' );
jimport( 'simpleschema.blog.post' );

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

class EasyblogApiResourceCategory extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );
	}
	
	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$category = EasyBlogHelper::getTable( 'Category', 'Table' );
		$id = $input->get('id', null, 'INT');
		$search = $input->get('search', null, 'STRING');
		$posts = array();
		
		
		if (!isset($id)) {
			$categoriesmodel = EasyBlogHelper::getModel( 'Categories' );
			$categories = $categoriesmodel->getCategoryTree('ordering');
			$this->plugin->setResponse( $categories );
			return;
		}
		
		$category->load($id);

		// private category shouldn't allow to access.
		$privacy	= $category->checkPrivacy();
		
		if(!$category->id || ! $privacy->allowed )
		{
			$this->plugin->setResponse( $this->getErrorResponse(404, 'Category not found') );
			return;
		}
		
		$catIds     = array();
		$catIds[]   = $category->id;
		EasyBlogHelper::accessNestedCategoriesId($category, $catIds);

		$sorting	= $this->plugin->params->get( 'sorting' , 'latest' );
		$total 		= (int) $this->plugin->params->get( 'total' , 20 );
		$rows 		= $model->getBlogsBy( 'category' , $catIds , $sorting , $total, EBLOG_FILTER_PUBLISHED, $search );
		
		foreach ($rows as $k => $v) {
			$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($v, '', 100, array('text'));
			$posts[] = $item;
		}
		
		$this->plugin->setResponse( $posts );
	}
	
}
