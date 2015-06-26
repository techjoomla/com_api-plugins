<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgAPIEasyblog extends ApiPlugin
{
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		$easyblog = JPATH_ROOT . '/administrator/components/com_easyblog/easyblog.php';
		if (!JFile::exists($easyblog) || !JComponentHelper::isEnabled('com_easysocial', true)) {
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
		$this->setResourceAccess('blog', 'public', 'post');
		$this->setResourceAccess('comments', 'public', 'get');
		$this->setResourceAccess('easyblog_users', 'public', 'get');
		
		$config 	= EasyBlogHelper::getConfig();
		if ($config->get('main_allowguestcomment')) {
			$this->setResourceAccess('comments', 'public', 'post');
		}

	}
}
