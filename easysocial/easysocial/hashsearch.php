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
