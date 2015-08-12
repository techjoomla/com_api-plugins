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
		$rows 		= $model->getBlogsBy( 'category' , $catIds , $sorting , 0, EBLOG_FILTER_PUBLISHED, $search );
		
		foreach ($rows as $k => $v) {
			$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($v, '', 100, array('text'));
			$item->isowner = ( $v->created_by == $this->plugin->get('user')->id )?true:false;
			
			if($v->blogpassword!='')
			{
                $item->ispassword=true;
            }
            else
            {
                $item->ispassword=false;
            }
            $item->blogpassword=$v->blogpassword;
            $model			= EasyBlogHelper::getModel( 'Ratings' );
			$ratingValue	= $model->getRatingValues( $item->postid, 'entry');
			$item->rate = $ratingValue;
			$item->isVoted = $model->hasVoted($item->postid,'entry',$this->plugin->get('user')->id);
			if($item->rate->ratings==0)
			{					
				$item->rate->ratings=-2;
			}			
			$posts[] = $item;
		}
		
		$this->plugin->setResponse( $posts );
	}
	
}
