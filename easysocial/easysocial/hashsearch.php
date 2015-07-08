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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/hashtags.php';

class EasysocialApiResourceHashsearch extends ApiResource
{
	public function get()
	{
	$this->plugin->setResponse($this->get_hash_list());			
	}	
	public function get_hash_list()
	{	
		/*search for hashtag */
		$app = JFactory::getApplication();
		/*accepting input*/
		$word = $app->input->get('type',NULL,'STRING');
		$obj = new EasySocialModelHashtags();
		/*calling method and return result*/	
		$result = $obj->search($word);
		return $result;	
	}	
}		
