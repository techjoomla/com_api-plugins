<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.user.user');
require_once( EBLOG_CONTROLLERS . '/media.php' );

class EasyblogApiResourceImage extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );
	}

	public function post()
	{    	
			$controller = new EasyBlogControllerMedia;
			$op = $controller->upload();
			
			// No setResponse needed since the upload method spits JSON and dies
	}
	
	public function get() {
		$this->plugin->setResponse( $this->getErrorResponse(404, __FUNCTION__ . ' not supported' ) );
	}
	
}
