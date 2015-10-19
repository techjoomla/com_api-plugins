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
jimport( 'joomla.libraries' );
jimport( 'joomla.media.foundry' );

use Joomla\Registry\Registry;

//require_once JPATH_SITE.'/plugins/api/articles/articles/helper/contenthelper.php';

require_once JPATH_SITE .'/components/com_content/models/articles.php';
require_once JPATH_SITE .'/components/com_content/models/article.php';
require_once JPATH_SITE .'/libraries/joomla/document/html/html.php';
require_once JPATH_SITE .'/libraries/cms/plugin/plugin.php';
require_once JPATH_SITE .'/libraries/cms/plugin/helper.php';
//require_once JPATH_SITE .'/components/com_content/models/categories.php';
//require_once JPATH_ADMINISTRATOR .'/components/com_categories/models/categories.php';
//require_once JPATH_SITE .'/libraries/legacy/model/list.php';
//require_once JPATH_SITE .'/components/com_content/helpers/query.php';
//require_once JPATH_SITE .'/libraries/joomla/document/document.php';

class ArticlesApiResourceArticle extends ApiResource
{
	public function get()
	{
		//init variable
		$app = JFactory::getApplication();
		
		$result = new stdClass();
		$items = array();
		$article_id	= $app->input->get('id', 0, 'INT');
		$catid	= $app->input->get('category_id', 0, 'INT');
		
		//featured - hide,only,show
		$featured	= $app->input->get('featured', '', 'STRING');
		$auther_id	= $app->input->get('auther_id', 0, 'INT');
		
		$limitstart	= $app->input->get('limitstart', 0, 'INT');
		$limit	= $app->input->get('limit', 0, 'INT');
		
		//range/relative - if range then startdate enddate mandetory 
		$date_filtering	= $app->input->get('date_filtering', '', 'STRING');
		$start_date = $app->input->get('start_date_range', '', 'STRING');
		$end_date = $app->input->get('end_date_range', '', 'STRING');
		$realtive_date = $app->input->get('relative_date', '', 'STRING');
		
		$listOrder = $app->input->get('listOrder', 'ASC', 'STRING');
		
		$art_obj = new ContentModelArticles();
		
		$art_obj->setState('list.direction', $listOrder);
		
		if($limit)
		{
			$art_obj->setState('list.start',$limitstart );
			$art_obj->setState('list.limit',$limit );
		}
		
		//filter by category
		if($catid)
		$art_obj->setState('filter.category_id',$catid );
		
		//filter by auther
		if($auther_id)
		$art_obj->setState('filter.author_id',$auther_id );
		
		//filter by featured
		if($featured)
		$art_obj->setState('filter.featured',$featured );
		
		//filter by article
		if($article_id)
		$art_obj->setState('filter.article_id',$article_id );
		
		//filtering
		if($date_filtering)
		{
			$art_obj->setState('filter.date_filtering',$date_filtering );
			if($date_filtering == 'range')
			{
				$art_obj->setState('filter.start_date_range',$start_date );
				$art_obj->setState('filter.end_date_range',$end_date );
			}
		}
		/*
		//get article data
		if($article_id)
		{
			$artcl = new ContentModelArticle();
			$adetails = $artcl->getItem($article_id);
			//$art_obj->setState('filter.article_id',$article_id );
		}*/
		
		$rows = $art_obj->getItems();
		
		//test code
		/*JPluginHelper::importPlugin( 'content' );
		$dispatcher = JEventDispatcher::getInstance();
		$results = $dispatcher->trigger( 'onContentPrepare', array( 'com_content', &$row , ) );
		*/
		//

		//format data
		$obj = new BlogappSimpleSchema();
		//$dispatcher = JEventDispatcher::getInstance();
		//$item = $obj->mapPost($a_data,'', 100, array('text'));
		foreach($rows as $row)
		{
			if(!isset($row->text)||empty($row->text))
			{
				$row->text = $row->introtext." ".$row->fulltext;
			}
			/*
			$document	= JFactory::getDocument();
			$document->setType('html');

			JPluginHelper::importPlugin( 'content' );
			$dispatcher = JEventDispatcher::getInstance();
			$dispatcher->trigger('onContentPrepare', array ('com_content.article', &$row, &$row->params, null));
			*/
			
			$items[] = $obj->mapPost($row,'', 100, array());
		}
		
		$this->plugin->setResponse($items);
		
	}
	
	public function post()
	{  
		//init variable
		$app = JFactory::getApplication();
		
		$result = new stdClass();
		$article_id	= $app->input->get('id', 0, 'INT');
		
		if(!$article_id)
		{
			$result->success = 0;
			$result->message = 'Please select article';
			$this->plugin->setResponse($result);
			return;
		}
		
		$art_obj = new ContentModelArticle();
		$art_obj->setId($article_id);
		$a_data = $art_obj->getArticle();
		
		//format data
		$obj = new BlogappSimpleSchema();
		//$item = $obj->mapPost($a_data,'', 100, array('text'));
		$item = $obj->mapPost($a_data,'', 100, array());
	
		$this->plugin->setResponse($item);
	}
			//~ 
	//~ function isValidEmail( $email )
	//~ {
		//~ $pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$";
//~ 
    	//~ if ( eregi( $pattern, $email )) {
    	  //~ return true;
      //~ } else {
        //~ return false;
      //~ }   
	//~ }
	
}
