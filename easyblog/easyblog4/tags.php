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
//for image upload
require_once( EBLOG_CLASSES . '/mediamanager.php' );
require_once( EBLOG_HELPERS . '/image.php' );
require_once( EBLOG_CLASSES . '/easysimpleimage.php' );
require_once( EBLOG_CLASSES . '/mediamanager/local.php' );
require_once( EBLOG_CLASSES . '/mediamanager/types/image.php' );
class EasyblogApiResourceTags extends ApiResource
{
	public function get()
	{
	$this->plugin->setResponse($this->getTags());
	}
	//method for search tags
	public function post()
	{
	$this->plugin->setResponse($this->searchTag());	
	}
	//get tags
	public function getTags()
	{
		$app = JFactory::getApplication();
		$limitstart = $app->input->get('limitstart',0,'INT');
	   	$limit =  $app->input->get('limit',20,'INT');
		$Tagmodel = EasyBlogHelper::getModel( 'Tags' );
		//$allTags = $Tagmodel->getTags();
		$allTags = $Tagmodel->getTagCloud();
		$allTags = array_slice($allTags, $limitstart, $limit);
		return $allTags;	
	}
	
	public function searchTag()
	{
	   $app = JFactory::getApplication();
	   $limitstart = $app->input->get('limitstart',0,'INT');
	   $limit =  $app->input->get('limit',20,'INT');
	   $Tagmodel = EasyBlogHelper::getModel( 'Tags' );
	   $input = JFactory::getApplication()->input;
	   $keyword = $input->get('title','', 'STRING');
	   $wordSearch = true;                
	   $db = EB::db();
	   $query = array();
	   $search = $wordSearch ? '%' . $keyword . '%' : $keyword . '%';
	   $query[] = 'SELECT * FROM ' . $db->quoteName('#__easyblog_tag');
	   $query[] = 'WHERE ' . $db->quoteName('title') . ' LIKE ' . $db->Quote($search);
	   $query[] = 'AND ' . $db->quoteName('published') . '=' . $db->Quote(1);

	   $query = implode(' ', $query);
	   $db->setQuery($query);
	   $result = $db->loadObjectList();
	   $output = array_slice($result, $limitstart, $limit);
	   return $output;
	}
}
