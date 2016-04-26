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
require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

//for image upload
require_once( EBLOG_CLASSES . '/mediamanager.php' );
require_once( EBLOG_HELPERS . '/image.php' );
require_once( EBLOG_CLASSES . '/easysimpleimage.php' );
require_once( EBLOG_CLASSES . '/mediamanager/local.php' );
require_once( EBLOG_CLASSES . '/mediamanager/types/image.php' );

class EasyblogApiResourceImage extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );
	}

	public function post()
	{
			//old  code
			/*$controller = new EasyBlogControllerMedia;
			$op = $controller->upload();
			*/
			
			$input = JFactory::getApplication()->input;
			$log_user = $this->plugin->get('user')->id;
			$res = new stdClass;
			// Let's get the path for the current request.
			$file	= JRequest::getVar( 'file' , '' , 'FILES' , 'array' );

			if($file['name'])
			{
			$place 	= 'user:'.$this->plugin->get('user')->id;
			
			// The user might be from a subfolder?
			$source	= urldecode('/'.$file['name']);

			// @task: Let's find the exact path first as there could be 3 possibilities here.
			// 1. Shared folder
			// 2. User folder
			$absolutePath 		= EasyBlogMediaManager::getAbsolutePath( $source , $place );
			$absoluteURI		= EasyBlogMediaManager::getAbsoluteURI( $source , $place );

			$allowed		= EasyImageHelper::canUploadFile( $file , $message );

			if( $allowed !== true )
			{
				$res->status= 0;
				$res->message = JText::_( 'PLG_API_EASYBLOG_UPLOAD_DENIED_MESSAGE' );
				return $res;
			}

			$media 				= new EasyBlogMediaManager();
			$upload_result		= $media->upload( $absolutePath , $absoluteURI , $file , $source , $place );

			//adjustment
			$upload_result->key = $place.$source;
			$upload_result->group = 'files';
			$upload_result->parentKey = $place.'|/';
			$upload_result->friendlyPath = 'My Media/'.$source;
			unset($upload_result->variations);
			$this->plugin->setResponse($upload_result);

			return $upload_result;
			
			}
			else
			{
				$this->plugin->setResponse( $this->getErrorResponse(404, __FUNCTION__ . JText::_( 'PLG_API_EASYBLOG_UPLOAD_UNSUCCESSFULL' ) ) );
			}
	}
	
	public function get() {
		$this->plugin->setResponse( $this->getErrorResponse(404, __FUNCTION__ . JText::_( 'PLG_API_EASYBLOG_NOT_SUPPORTED' ) ) );
	}
	
}
