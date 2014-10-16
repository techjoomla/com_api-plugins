<?php
defined('_JEXEC') or die( 'Restricted access' );
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

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
