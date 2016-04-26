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

		$limitstart = $input->get('limitstart',0,'INT');
		$limit = $input->get('limit',10,'INT');

		$posts = array();
		
		
		if (!isset($id)) {
			$categoriesmodel = EasyBlogHelper::getModel( 'Categories' );
			$categories = $categoriesmodel->getCategoryTree('ordering');

			$categories = array_slice($categories, $limitstart,$limit);

			$this->plugin->setResponse( $categories );
			return;
		}
		
		$category->load($id);

		// private category shouldn't allow to access.
		$privacy	= $category->checkPrivacy();
		
		if(!$category->id || ! $privacy->allowed )
		{
			$this->plugin->setResponse( $this->getErrorResponse(404, JText::_( 'PLG_API_EASYBLOG_CATEGORY_NOT_FOUND_MESSAGE' )) );
			return;
		}
		
		$catIds     = array();
		$catIds[]   = $category->id;
		EasyBlogHelper::accessNestedCategoriesId($category, $catIds);

		$sorting	= $this->plugin->params->get( 'sorting' , 'latest' );
		$rows 		= $model->getBlogsBy( 'category' , $catIds , $sorting , 0, EBLOG_FILTER_PUBLISHED, $search );
		
		foreach ($rows as $k => $v) {
			
			$scm_obj = new EasyBlogSimpleSchema_4();
			$item = $scm_obj->mapPost($v, '', 100, array('text'));
			
			//$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($v, '', 100, array('text'));
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
		$posts = array_slice($posts, $limitstart,$limit);
		$this->plugin->setResponse( $posts );
	}
	
}
