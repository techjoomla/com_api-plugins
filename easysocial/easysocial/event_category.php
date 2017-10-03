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
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourceEvent_category
 *
 * @since  1.0
 */
class EasysocialApiResourceEvent_Category extends ApiResource
{
	/**
	 * Method get
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->get_cat();
	}

	/**
	 * Method getting all categories of event
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get_cat()
	{
		$app = JFactory::getApplication();

		// Getting log_user
		$log_user = $this->plugin->get('user')->id;
		$cat = FD::model('eventcategories');

		// Response object
		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		$res->result = $cat->getCategories();
		$this->plugin->setResponse($res);
	}
}
