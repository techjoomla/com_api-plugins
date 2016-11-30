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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/fields.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceGroup_category extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->getCategory());
	}

	public function post()
	{
		$this->plugin->setResponse(JText::_( 'PLG_API_EASYSOCIAL_USE_GET_METHOD_MESSAGE' ));
	}
	
	//function use for get friends data
	function getCategory()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		$other_user_id = $app->input->get('user_id',0,'INT'); 
		$userid = ($other_user_id)?$other_user_id:$log_user;
		$data = array();
		$mapp = new EasySocialApiMappingHelper();
		$user = FD::user($userid);
		
		// Get a list of group categories
		$catModel = FD::model('GroupCategories');
		$cats = $catModel->getCategories(array('state' => SOCIAL_STATE_PUBLISHED, 'ordering' => 'ordering'));
		$data['data'] = $mapp->mapItem($cats,'category',$log_user);
		return( $data );
	}
}
