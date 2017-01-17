<?php
/**
 * @package	API
 * @version 1.5
 * @author 	Brian Edgerton
 * @link 	http://www.edgewebworks.com
 * @copyright Copyright (C) 2011 Edge Web Works, LLC. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
jimport('joomla.user.helper');
jimport('joomla.application.component.model');
jimport( 'joomla.application.component.model' );
jimport( 'joomla.database.table.user' );

use Joomla\Registry\Registry;

require_once JPATH_SITE .'/components/com_content/models/article.php';
require_once JPATH_SITE .'/components/com_content/models/category.php';
//require_once JPATH_SITE .'/components/com_tz_portfolio/models/category.php';
require_once JPATH_ADMINISTRATOR .'/components/com_categories/models/categories.php';
//require_once JPATH_ADMINISTRATOR .'/components/com_tz_portfolio/models/categories.php';
require_once JPATH_SITE .'/libraries/legacy/model/list.php';
require_once JPATH_SITE .'/libraries/cms/menu/menu.php';
require_once JPATH_SITE .'/components/com_content/helpers/query.php';

JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tz_portfolio/models', 'TZ_PortfolioModel');


class ArticlesApiResourceCategory extends ApiResource
{
	public function get()
	{
		//init variable
		$app = JFactory::getApplication();
		//get data
		$catid		= $app->input->get('id', 0, 'INT');
		$limitstart	= $app->input->get('limitstart', 0, 'INT');
		$limit	= $app->input->get('limit', 10, 'INT');
		$menu_id	= $app->input->get('menu_id', 0, 'INT');
		$mcats = array();
		//get category by custom menu
		if($menu_id)
		{
			$menu      = $app->getMenu('site');
			$val = $menu->getItem($menu_id);
			$menu_cat = json_decode($val->params)->tz_catid;
			$db = JFactory::getDbo();
		
			foreach($menu_cat as $ky => $cid )
			{
				/*if($ky != 0)
				{			
					$db->setQuery("SELECT * FROM #__categories cat WHERE cat.id='$cid'");
					$mcats[] = $db->loadObject();
				}*/
				$db->setQuery("SELECT * FROM #__categories cat WHERE cat.published = 1 AND cat.id='$cid'");
					$mcats[] = $db->loadObject();
				
			}
	
		}
		$cat_obj = new CategoriesModelCategories();
		//$cat_obj = new TZ_PortfolioModelCategories();
		$jlist = new JModelList();

		$config = JFactory::getConfig();
		$old_limit = $config->get('list_limit');
        $config->set('list_limit', 0);
		
		//$jlist->setState('list.start', 0);
		//$jlist->setState('list.limit', 10);

		$items = array();
		//format data
		$obj = new BlogappSimpleSchema();
		
		if($catid)
		{
			$rows = $this->getCatArticle($catid,$limit,$limitstart);
			
			if($rows != null)
			{
				foreach( $rows as $row )
				{
					$items[] = $obj->mapPost($row,'', 100, array());
				}
			}
		}
		else
		{
			if(empty($mcats))
			{
				$rows = $cat_obj->getItems();
			}
			else
			{
				$rows = $mcats;
			}
			foreach( $rows as $row )
			{
				$items[] = $obj->mapCategory($row);
			}
		}
		
		$config->set('list_limit', $old_limit);
		
		$items = array_slice($items,$limitstart,$limit);
		
		$this->plugin->setResponse($items);
	}
	
	//get catgory article
	public function getCatArticle($catid,$limit,$limitstart)
	{
		//init variable
		$app = JFactory::getApplication();
		// Get the dbo
		$db = JFactory::getDbo();
		//get data
		$catid		= $app->input->get('id', 0, 'INT');
		$featured		= $app->input->get('featured', 0, 'INT');
		$direction		= $app->input->get('direction', 'ASC', 'STRING');
		
		//$cat_obj = new ContentModelCategory();
		//$cat_obj = new TZ_PortfolioModelCategory();

		$model = JModelLegacy::getInstance('Articles', 'TZ_PortfolioModel', array('ignore_request' => true));

		$model->setState('params', JFactory::getApplication()->getParams());
		$model->setState('filter.category_id', $catid);
		$model->setState('filter.published', 1);
		
		$model->setState('filter.featured', $featured);
		//$model->setState('list.ordering', $cat_obj->_buildContentOrderBy());
		$model->setState('list.start', $limitstart);
		$model->setState('list.limit', $limit);
		$model->setState('list.direction', $direction);
		//$model->setState('list.filter', $this->getState('list.filter'));

		// Filter.subcategories indicates whether to include articles from subcategories in the list or blog
		$model->setState('filter.subcategories', 1);
		$model->setState('filter.max_category_levels', 3);
		//$model->setState('list.links', $this->getState('list.links'));
		
		$data = $model->getItems();

		return $data;
	}
	
	public function post()
	{  
		$this->plugin->setResponse("use post method");
	}
	
}
