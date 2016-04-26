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

class EasyblogApiResourceCategory extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );
	}
	
	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$category = EasyBlogHelper::table( 'Category', 'Table' );
		$id = $input->get('id', null, 'INT');
		$search = $input->get('search', null, 'STRING');
		
		$limitstart = $input->get('limitstart', 0, 'INT');
		$limit = $input->get('limit', 10, 'INT');

		$posts = array();
		
		
		if (!isset($id)) {
			$categoriesmodel = EasyBlogHelper::getModel( 'Categories' );
			$categories = $categoriesmodel->getCategoryTree('ordering');
			
			$categories = array_slice($categories,$limitstart,$limit);			
			
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

		//new code
		$category = EB::table('Category');
		$category->load($id);
		
		//get the nested categories
		$category->childs = null;
		// Build nested childsets
		EB::buildNestedCategories($category->id, $category, false, true);
		
		$catIds = array();
		$catIds[] = $category->id;

		EB::accessNestedCategoriesId($category, $catIds);
		// Get the category model
		$model = EB::model('Category');

		// Get total posts in this category
		$category->cnt = $model->getTotalPostCount($category->id);

		// Get teamblog posts count
		// $teamBlogCount = $model->getTeamBlogCount($category->id);

		//$limit = EB::call('Pagination', 'getLimit', array(EBLOG_PAGINATION_CATEGORIES));

		// Get the posts in the category
		$data = $model->getPosts($catIds);
		$rows = EB::formatter('list', $data);
	
		$rows = array_slice($rows,$limitstart,$limit);
		//end
		
		foreach ($rows as $k => $v) {
			//$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($v, '', 100, array('text'));
			$scm_obj = new EasyBlogSimpleSchema_plg();
			$item = $scm_obj->mapPost($v,'', 100, array('text'));
			
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
