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


	class EasysocialApiResourceTerms extends ApiResource
	{
		public function get()
		{		
		$this->plugin->setResponse($this->content());		
		}
	
		public function post()
		{
			$this->plugin->setResponse("Use method get");
		}
	
		public function content()
		{		
			$res = new stdClass();						
			$app = 'aaa';
			//print_r($app);die();
			$company = Appcarverse;

			//$res->message = JText::sprintf(
			//	'PLG_API_EASYSOCIAL_APP_TERM_TWO',
			//	$app
			//);



			//$res->message = JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_TWO %s', $app);
			//$res->message = JText::printf('PLG_API_EASYSOCIAL_APP_TERM_TWO', $app);
			//print_r($res->message);die();
		

			$res->message = "<h3>".JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_ONE', $app).JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_TWO', $app).JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_THREE', $app).JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_FOUR', $app)."</h3>";
		
			return $res;		
		}		
	}


