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
jimport('joomla.application.component.controller');
jimport('joomla.application.component.model');
jimport('joomla.user.helper');
jimport('joomla.user.user');
jimport('joomla.application.component.helper');

JModelLegacy::addIncludePath(JPATH_SITE.'components/com_api/models');
require_once JPATH_SITE.'/components/com_api/libraries/authentication/user.php';
require_once JPATH_SITE.'/components/com_api/libraries/authentication/login.php';

class UsersApiResourceConfig extends ApiResource
{
	public function get()
	{
		$obj = new stdClass;
		//get joomla,easyblog and easysocial configuration
		//get version of easysocial and easyblog
		$easyblog = JPATH_ADMINISTRATOR .'/components/com_easyblog/easyblog.php';
		$easysocial = JPATH_ADMINISTRATOR .'/components/com_easysocial/easysocial.php';
		//eb version
		if( JFile::exists( $easyblog ) )
		{
			$obj->easyblog = $this->getCompParams('com_easyblog','easyblog');
		}
		//es version
		if( JFile::exists( $easysocial ) )
		{
			/*$xml = JFactory::getXML(JPATH_ADMINISTRATOR .'/components/com_easysocial/easyblog.xml');
			$obj->easysocial_version = (string)$xml->version;*/
			$obj->easysocial = $this->getCompParams( 'com_easysocial','easysocial' );
		}

		$obj->global_config = $this->getJoomlaConfig();
		//	

		$this->plugin->setResponse($obj);	
	}

	public function post()
	{
	   $this->plugin->setResponse( JText::_( 'PLG_API_USERS_UNSUPPORTED_METHOD_POST' ));
	}

	
	//get component params
	public function getCompParams($cname=null,$name=null)
	{
		jimport('joomla.application.component.helper');
		$app = JFactory::getApplication();
		$cdata = array();
	
		$xml = JFactory::getXML(JPATH_ADMINISTRATOR .'/components/'.$cname.'/'.$name.'.xml');
		$cdata['version'] = (string)$xml->version;
		$jconfig = JFactory::getConfig();
		
		if( $cname == 'com_easyblog' )
		{
		       /*$xml = JFactory::getXML(JPATH_ADMINISTRATOR .'/components/com_easyblog/easyblog.xml');
                       $version = (string)$xml->version;*/  

                       if($cdata['version']<5)
                       {        
                          require_once( JPATH_ROOT . '/components/com_easyblog/helpers/helper.php' );
                               $eb_params        = EasyBlogHelper::getConfig();
                       }
                       else
                       {        
                          require_once JPATH_ADMINISTRATOR.'/components/com_easyblog/includes/easyblog.php';
                               $eb_params = EB::config();
                       }

			$cdata['main_max_relatedpost'] = $eb_params->get('main_max_relatedpost');
			$cdata['layout_pagination_bloggers'] = $eb_params->get('layout_pagination_bloggers');
			$cdata['layout_pagination_categories'] = $eb_params->get('layout_pagination_categories');
			$cdata['layout_pagination_categories_per_page'] = $eb_params->get('layout_pagination_categories_per_page');
			$cdata['layout_pagination_bloggers_per_page'] = $eb_params->get('layout_pagination_bloggers_per_page');
			$cdata['layout_pagination_archive'] = $eb_params->get('layout_pagination_archive');
			$cdata['layout_pagination_teamblogs'] = $eb_params->get('layout_pagination_teamblogs');
	
		}
		else
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
			$es_params = FD::config();

			$cdata['conversations_limit'] = $es_params->get('conversations')->limit;
			$cdata['activity_limit'] = $es_params->get('activity')->pagination;
			$cdata['lists_limit'] = $es_params->get('lists')->display->limit;
			$cdata['comments_limit'] = $es_params->get('comments')->limit;
			$cdata['stream_pagination_limit'] = $es_params->get('stream')->pagination->pagelimit;
			$cdata['photos_pagination_limit'] = $es_params->get('photos')->pagination->photo;
			$cdata['album_pagination_limit'] = $es_params->get('photos')->pagination->album;
			$cdata['emailasusername'] = $es_params->get('registrations')->emailasusername;
			$cdata['displayName'] = $es_params->get('users')->displayName;
			$cdata['groups']['enabled'] = $es_params->get('groups')->enabled;

		}
		return $cdata;
	}
	
	//get joomla config changes
	public function getJoomlaConfig()
	{
		$jconfig = JFactory::getConfig();
		$jarray = array();
		$jarray['global_list_limit'] = $jconfig->get('list_limit');
		$jarray['offset'] = $jconfig->get('offset');
		$jarray['offset_user'] = $jconfig->get('offset_user');
		
		return $jarray;
	}	
	
	/*
	 * function to update Easyblog auth keys
	 */
	public function updateEauth($user=null,$key=null)
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
		$model 	= FD::model('Users');
		$id 	= $model->getUserId('username', $user->username);
		$user 	= FD::user($id);
		$user->alias = $user->username;
		$user->auth = $key;
		$user->store();
	
		return $id;
	}
}
