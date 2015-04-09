<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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
		//print_r($FILES);die("in post grp api");
	   $this->plugin->setResponse("use get method");
	}
	
	//function use for get friends data
	function getCategory()
	{
		//init variable
		$app = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		
		$other_user_id = $app->input->get('user_id',0,'INT'); 

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
