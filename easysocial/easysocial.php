<?php
/**
 * @package    API_Plugins
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

// Added this line to EasySocial api compatible with EasySocial 2.1.0 package
ES::import('site:/controllers/controller');

/** plgAPIEasysocial
 *
 * @since  1.8.8
 */
class PlgAPIEasysocial extends ApiPlugin
{
	/** Construct
	 * 
	 * @param   int  &$subject  subject
	 * @param   int  $config    config
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject = 'api', $config = array());

		ApiResource::addIncludePath(dirname(__FILE__) . '/easysocial');

		/*load language file for plugin frontend*/
		$lang = JFactory::getLanguage();
		$lang->load('plg_api_easysocial', JPATH_ADMINISTRATOR, '', true);
		$lang->load('com_easysocial', JPATH_ADMINISTRATOR, '', true);
		$lang->load('com_easysocial', JPATH_SITE, '', true);
		$lang->load('com_users', JPATH_SITE, '', true);
		$this->setResourceAccess('terms', 'public', 'post');
		$this->setResourceAccess('sociallogin', 'public', 'post');
	}
}
