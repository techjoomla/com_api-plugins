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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * API class EasysocialApiResourceTerms
 *
 * @since  1.0
 */
class EasysocialApiResourceTerms extends ApiResource
{
	/**	  
	 * Function for get
	 * 	 
	 * @return  JSON
	 */
	public function get()
	{
	$this->plugin->setResponse("Use method post");
	}

	/**	  
	 * Function for post appname and organization content
	 * 	 
	 * @return  JSON
	 */
	public function post()
	{
		$this->plugin->setResponse($this->content());
	}

	/**
	 * get videos throught api
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function content()
	{
		$res = new stdClass;
		$jinput = Factory::getApplication();
		$app = $jinput->input->get('appname', null, 'STRING');
		$company = $jinput->input->get('company', 'Appcarvers', 'STRING');
		$day = $jinput->input->get('day', 30, 'INT');
		/* $res->message = "<h3>" .
						Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_ONE', $company) .
						Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_TWO', $app, $company) .
						Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_THREE', $company) .
						Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_FOUR', $company, $day) . "</h3>";
		*/

		$res->message = Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_ONE', $company);
		$res->message_one = Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_TWO', $app, $company);
		$res->subtitle = Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_THREE', $company);
		$res->rules = Text::sprintf('PLG_API_EASYSOCIAL_APP_TERM_FOUR', $company, $day);

		return $res;
	}
}
