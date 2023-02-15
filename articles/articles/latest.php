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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

jimport( 'joomla.application.component.model' );
jimport( 'joomla.database.table.user' );

if (JVERSION < '4.0.0')
{
	require_once( JPATH_SITE.'/components/com_content/models/article.php');
	require_once( JPATH_SITE.'/components/com_content/models/category.php');
	require_once( JPATH_SITE.'/components/com_content/models/article.php');
	require_once( JPATH_SITE.'/components/com_content/helpers/query.php');
	require_once JPATH_SITE . '/components/com_content/helpers/route.php';
}
else
{
	require_once JPATH_SITE . '/components/com_content/src/Model/ArticleModel.php';
	require_once JPATH_SITE . '/components/com_content/src/Model/CategoryModel.php';
	require_once JPATH_SITE . '/components/com_content/src/Helper/QueryHelper.php';
	require_once JPATH_SITE . '/components/com_content/src/Service/Router.php';
}

class ArticlesApiResourceLatest extends ApiResource
{
	
	public function get()
	{
		$this->plugin->setResponse($this->getLatest());
	}
	//get latest article
	public function getLatest()
	{
		// Get the dbo
		$db = Factory::getDbo();
		$result = new stdClass();

		// Get an instance of the generic articles model
		$model = BaseDatabaseModel::getInstance('Articles', 'ContentModel', array('ignore_request' => true));
		$plugin = PluginHelper::getPlugin('api', 'articles');

		if ($plugin)
		{
		$params = new Registry($plugin->params);
		
		// Set application parameters in model
		$app       = Factory::getApplication();
		$appParams = $app->getParams();
		$model->setState('params', $appParams);

		// Set the filters based on the module params
		$model->setState('list.start', 0);
		$model->setState('list.limit', (int) $params->get('count', 5));
		$model->setState('filter.published', 1);

		// Access filter
		$access     = !ComponentHelper::getParams('com_content')->get('show_noauth');
		$authorised = Access::getAuthorisedViewLevels(Factory::getUser()->get('id'));
		$model->setState('filter.access', $access);

		// Category filter
		$model->setState('filter.category_id', $params->get('catid', array()));

		// User filter
		$userId = Factory::getUser()->get('id');

		switch ($params->get('user_id'))
		{
			case 'by_me' :
				$model->setState('filter.author_id', (int) $userId);
				break;
			case 'not_me' :
				$model->setState('filter.author_id', $userId);
				$model->setState('filter.author_id.include', false);
				break;

			case '0' :
				break;

			default:
				$model->setState('filter.author_id', (int) $params->get('user_id'));
				break;
		}

		// Filter by language
		$model->setState('filter.language', $app->getLanguageFilter());

		//  Featured switch
		switch ($params->get('show_featured'))
		{
			case '1' :
				$model->setState('filter.featured', 'only');
				break;
			case '0' :
				$model->setState('filter.featured', 'hide');
				break;
			default :
				$model->setState('filter.featured', 'show');
				break;
		}

		// Set ordering
		$order_map = array(
			'm_dsc' => 'a.modified DESC, a.created',
			'mc_dsc' => 'CASE WHEN (a.modified = ' . $db->quote($db->getNullDate()) . ') THEN a.created ELSE a.modified END',
			'c_dsc' => 'a.created',
			'p_dsc' => 'a.publish_up',
			'random' => 'RAND()',
		);
		$ordering = ArrayHelper::getValue($order_map, $params->get('ordering'), 'a.publish_up');
		$dir      = 'DESC';

		$model->setState('list.ordering', $ordering);
		$model->setState('list.direction', $dir);

		$items = $model->getItems();

		//format data
		$obj = new BlogappSimpleSchema();

		foreach ($items as &$item)
		{
			$item->slug    = $item->id . ':' . $item->alias;
			$item->catslug = $item->catid . ':' . $item->category_alias;
			
			if ($access || in_array($item->access, $authorised))
			{
				// We know that user has the privilege to view the article
				$item->link = Route::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));
			}
			else
			{
				$item->link = Route::_('index.php?option=com_users&view=login');
			}
			
			$item = $obj->mapPost($item,'', 100, array());
			
		}
		
		$result->success = 1;
		$result->data = $items;
	 
		}
		else
		{
			$result->success = 0;
			$result->data = 0;
			$result->message = "Plugin params not found please, install and save plugin params";
			
		}
		return $result;
	}
	/*public function get()
	{
		
		$result = new stdClass();
		//$article_id	= Factory::getApplication()->input->get('id', 0, '', 'int');
		
		$ordering = Factory::getApplication()->input->get('ordering', 'c_dsc', '', 'string');
		$catid = Factory::getApplication()->input->get('catid', '0', '', 'int');
		$secid = Factory::getApplication()->input->get('section_id', '0', '', 'int');
		
		$target_blogs = Factory::getApplication()->input->get('my_blogs', '0', '', 'string');
		$target_user = Factory::getApplication()->input->get('target_user', 0, '', 'int');
		
		$show_front = Factory::getApplication()->input->get('show_front', '0', '', 'string');
		$search = Factory::getApplication()->input->get('search', '', '', 'string');
		
		$limit		= Factory::getApplication()->input->get('limit', 20, '', 'int');
		$limitstart	= Factory::getApplication()->input->get('limitstart', 0, '', 'int');
		
		$db	= Factory::getDbo();
		
		$contentConfig = ComponentHelper::getParams( 'com_content' );
		$access		= !$contentConfig->get('show_noauth');

		$nullDate	= $db->getNullDate();

		$date = Factory::getDate();
		$now = $date->toSQL();

		$where		= 'a.state = 1'
			. ' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
			. ' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'
			;

		// User Filter
		if($target_blogs)
		{
			$log_user = $this->plugin->get('user')->id;
			$user		= Factory::getUser($log_user);
		
			switch ($target_blogs)
			{
				case 'by_me':
					$where .= ' AND (created_by = ' . (int) $user->id . ' OR modified_by = ' . (int) $user->id . ')';
					break;
				case 'not_me':
					$where .= ' AND (created_by <> ' . (int) $user->id . ' AND modified_by <> ' . (int) $user->id . ')';
					break;
			}
		}
		//search data
		if($search)
		{
			//$where .= "AND  a.title LIKE '% " . $search . " %' OR a.text LIKE '% " . $search . " %')";
			$where .= "AND  a.title LIKE '% " . $search . " %' ";
		}
		
		// Ordering
		switch ($ordering)
		{
			case 'm_dsc':
				$ordering		= 'a.modified DESC, a.created DESC';
				break;
			case 'c_dsc':
			default:
				$ordering		= 'a.created DESC';
				break;
		}

		if ($catid)
		{
			$ids = explode( ',', $catid );
			JArrayHelper::toInteger( $ids );
			$catCondition = ' AND (cc.id=' . implode( ' OR cc.id=', $ids ) . ')';
		}
		
		// Content Items only
		$query = 'SELECT a.*, ' .
			' CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug,'.
			' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'.
			' FROM #__content AS a' .
			($show_front == '0' ? ' LEFT JOIN #__content_frontpage AS f ON f.content_id = a.id' : '') .
			' INNER JOIN #__categories AS cc ON cc.id = a.catid' .
			' INNER JOIN #__sections AS s ON s.id = a.sectionid' .
			' WHERE '. $where .' AND s.id > 0' .
			($access ? ' AND a.access <= ' .(int) $aid. ' AND cc.access <= ' .(int) $aid. ' AND s.access <= ' .(int) $aid : '').
			($catid ? $catCondition : '').
			($secid ? $secCondition : '').
			($show_front == '0' ? ' AND f.content_id IS NULL ' : '').
			' AND s.published = 1' .
			' AND cc.published = 1' .
			' ORDER BY '. $ordering;
		
		//echo $query;
		if($limit)
		{
			$query = $query." LIMIT ".$limitstart.",".$limit;
		}

		$db->setQuery($query, 0, $count);
		$rows = $db->loadObjectList();

		//format data
		$obj = new BlogappSimpleSchema();

		$items = array();
		foreach($rows as $row)
		{
			$items[] = $obj->mapPost($row,'', 100, array());
		}
		$this->plugin->setResponse($items);
	}*/
	
	public function post()
	{  
		$this->plugin->setResponse("Use get method");
	}
}
