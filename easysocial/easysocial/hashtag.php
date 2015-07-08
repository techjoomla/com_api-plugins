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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/stream.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/hashtags.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceHashtag extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->get_hash_list());
	}
	//get hashtag list
	public function get_hash_list()
	{	
		/*search for hashtag */
		$app = JFactory::getApplication();
		/*accepting input*/
		$word = $app->input->get('tag',NULL,'STRING');
		$obj = new EasySocialModelHashtags();
		/*calling method and return result*/
		$result = $obj->search($word);
		return $result;	
	}	
}
