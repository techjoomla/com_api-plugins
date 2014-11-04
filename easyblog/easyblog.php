<?php
/**
 * @package	API
 * @version 1.5.1
 * @author 	Techjoomla
 * @link 	http://techjoomla.com
 * @copyright Copyright (C) 2014 Techjoomla. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgAPIEasyblog extends ApiPlugin
{
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		$easyblog 	= JPATH_ROOT . '/administrator/components/com_easyblog/easyblog.php';
		if (!JFile::exists($easyblog)) {
			ApiError::raiseError(404, 'Easyblog not installed');
			return;
		}

		// Load Easyblog language & bootstrap files
		$language = JFactory::getLanguage();
		$language->load('com_easyblog');
		require_once( JPATH_ROOT . '/components/com_easyblog/constants.php' );
		require_once( EBLOG_HELPERS . '/helper.php' );

		// Set resources & access
		ApiResource::addIncludePath(dirname(__FILE__).'/easyblog');
		$this->setResourceAccess('latest', 'public', 'get');
		$this->setResourceAccess('category', 'public', 'get');
		$this->setResourceAccess('blog', 'public', 'get');
		$this->setResourceAccess('comments', 'public', 'get');
		
		$config 	= EasyBlogHelper::getConfig();
		if ($config->get('main_allowguestcomment')) {
			$this->setResourceAccess('comments', 'public', 'post');
		}

	}
}
