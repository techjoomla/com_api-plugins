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

require_once( JPATH_SITE .DS.'components'.DS.'com_content'.DS.'models'.DS.'article.php');
require_once( JPATH_SITE .DS.'components'.DS.'com_content'.DS.'models'.DS.'category.php');
require_once( JPATH_SITE .DS.'components'.DS.'com_content'.DS.'models'.DS.'article.php');
require_once( JPATH_SITE .DS.'components'.DS.'com_content'.DS.'helpers'.DS.'query.php');

class ArticlesApiResourceLatest extends ApiResource
{
	public function get()
	{
		
		$result = new stdClass();
		//$article_id	= JRequest::getVar('id', 0, '', 'int');
		$target_user = JRequest::getVar('target_user', 0, '', 'int');
		$ordering = JRequest::getVar('ordering', 'c_dsc', '', 'string');
		$catid = JRequest::getVar('catid', '0', '', 'int');
		$secid = JRequest::getVar('section_id', '0', '', 'int');
		$target_blogs = JRequest::getVar('my_blogs', '0', '', 'string');
		$show_front = JRequest::getVar('show_front', '0', '', 'string');
		$search = JRequest::getVar('search', '', '', 'string');
		
		$limit		= JRequest::getVar('limit', 20, '', 'int');
		$limitstart	= JRequest::getVar('limitstart', 0, '', 'int');
		
		$db	= JFactory::getDBO();
		$log_user = $this->plugin->get('user')->id;
		$user		= JFactory::getUser($log_user);
		/*if(!$article_id)
		{
			$result->success = 0;
			$result->message = 'Please select article';
			$this->plugin->setResponse($result);
			return;
		}*/
		
		$contentConfig = JComponentHelper::getParams( 'com_content' );
		$access		= !$contentConfig->get('show_noauth');

		$nullDate	= $db->getNullDate();

		$date = JFactory::getDate();
		$now = $date->toMySQL();

		$where		= 'a.state = 1'
			. ' AND ( a.publish_up = '.$db->Quote($nullDate).' OR a.publish_up <= '.$db->Quote($now).' )'
			. ' AND ( a.publish_down = '.$db->Quote($nullDate).' OR a.publish_down >= '.$db->Quote($now).' )'
			;

		// User Filter
		if($target_blogs)
		{
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
		/*if ($secid)
		{
			$ids = explode( ',', $secid );
			JArrayHelper::toInteger( $ids );
			$secCondition = ' AND (s.id=' . implode( ' OR s.id=', $ids ) . ')';
		}*/

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
	echo $query; 
		$db->setQuery($query, 0, $count);
		$rows = $db->loadObjectList();

		/*$art_obj = new ContentModelArticle();
		$art_obj->setId($article_id);
		$a_data = $art_obj->getArticle();*/
		
		//format data
		$obj = new BlogappSimpleSchema();

		$items = array();
		foreach($rows as $row)
		{
			$items[] = $obj->mapPost($row,'', 100, array());
		}
		$this->plugin->setResponse($items);
	}
	
	public function post()
	{  
		$this->plugin->setResponse("Use get method");
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
