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
		$this->plugin->setResponse("Use method post");		
			
		}
	
		public function post()
		{
			$this->plugin->setResponse($this->content());	
		}
	
		public function content()
		{		
			$res = new stdClass();						
			//$app = 'Easysocial';
			//$company = 'Appcarvers';
			//$day = 30;	

			$jinput = JFactory::getApplication();
                               
			$app = $jinput->input->get('appname',null,'STRING');	

			$company = $jinput->input->get('company','Appcarvers','STRING');
			$day = $jinput->input->get('day',30,'INT');		

			$res->message = "<h3>".JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_ONE', $company).JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_TWO', $app, $company).JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_THREE', $company).JText::sprintf('PLG_API_EASYSOCIAL_APP_TERM_FOUR', $company, $day)."</h3>";
		
			return $res;		
		}		
	}


