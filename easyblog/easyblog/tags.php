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
	//future requirement.
	//~ public function post()
	//~ {
	//~ $this->plugin->setResponse($this->searchTag());	
	//~ }
	public function getTags()
	{
		$Tagmodel = EasyBlogHelper::getModel( 'Tags' );
		//$allTags = $Tagmodel->getTags();
		$allTags = $Tagmodel->getTagCloud();
		return $allTags;	
	}
	//future requirement.
	//~ public function searchTag()
	//~ {
		//~ $Tagmodel = EasyBlogHelper::getModel( 'Tags' );
		//~ $input = JFactory::getApplication()->input;
		//~ $title = $input->get('title', null, 'STRING');
		//~ $result = $Tagmodel->searchTag($title);
		//~ return $result;
	//~ }
}
