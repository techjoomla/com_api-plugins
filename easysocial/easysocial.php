<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
/**
 * API class PlgAPIEasysocial
 *
 * @since  1.0
 */
class PlgAPIEasysocial extends ApiPlugin
{
	/**
	 * Method Constuctor
	 *
	 * @param   object  &$subject  reference to subject
	 * @param   array   $config    configuration array
	 *
	 * @since 1.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		ApiResource::addIncludePath(dirname(__FILE__) . '/easysocial');

		/* load language file for plugin frontend */
		$lang = JFactory::getLanguage();
		$lang->load('plg_api_easysocial', JPATH_ADMINISTRATOR, '', true);
		$lang->load('com_easysocial', JPATH_ADMINISTRATOR, '', true);
		$lang->load('com_easysocial', JPATH_SITE, '', true);
		$lang->load('com_users', JPATH_SITE, '', true);
		$this->setResourceAccess('terms', 'public', 'post');
		$this->setResourceAccess('sociallogin', 'public', 'post');
	}
}
