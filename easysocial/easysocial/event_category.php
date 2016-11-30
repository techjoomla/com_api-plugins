<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceEvent_category extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->get_cat());
	}

	//getting all categories of event
	public function get_cat()
	{
		$app = JFactory::getApplication();

		//getting log_user
		$log_user = $this->plugin->get('user')->id;		
		$cat=FD::model( 'eventcategories' );
		$res=$cat->getCategories();
		return $res;
	}	
}
