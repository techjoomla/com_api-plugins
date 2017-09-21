<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2017 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
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
	/**
	 * Method get
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$obj = new stdClass;
		$result = new stdClass;
		
		$app         = JFactory::getApplication();
		$acl = $app->input->get('acl', false, boolean);
		$userId = $app->input->get('userId', 0, 'INT');

		if ($acl && $userId != 0)
		{
			$this->getProfileACL($userId);
		}
		else{
			
		// get joomla,easyblog and easysocial configuration
		// get version of easysocial and easyblog
		
		$easyblog = JPATH_ADMINISTRATOR . '/components/com_easyblog/easyblog.php';
		$easysocial = JPATH_ADMINISTRATOR . '/components/com_easysocial/easysocial.php';
		
		// eb version
		if( JFile::exists( $easyblog ) )
		{
			$obj->easyblog = $this->getCompParams('com_easyblog', 'easyblog');
		}
		
		// es version
		if (JFile::exists($easysocial))
		{
			$obj->easysocial = $this->getCompParams( 'com_easysocial', 'easysocial' );
		}

		$obj->global_config = $this->getJoomlaConfig();
		$obj->plugin_config = $this->getpluginConfig();
		
		
		$installed_languages = JLanguageHelper::getLanguages();
		$languages	=	array();
		
		foreach($installed_languages as $lang){
			$languages[] = substr($lang->lang_code, 0, 2);
		}
		
		$obj->languages	=	$languages;
		
		$xml = JFactory::getXML(JPATH_SITE . '/plugins/api/users/users.xml');
		$obj->plugin_version = (string)$xml->version;
		
		$result->result = $obj;
		
		$this->plugin->setResponse($result);
	}
	}

	/**
	 * Method post
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	 
	public function post()
	{
	   $this->plugin->setResponse( JText::_( 'PLG_API_USERS_UNSUPPORTED_METHOD_POST' ));
	}
	
	/**
	 * Method getCompParams
	 *
	 * @return  mixed get component params
	 *
	 * @since 1.0
	 */
	public function getCompParams($cname=null,$name=null)
	{
		jimport('joomla.application.component.helper');
		$app = JFactory::getApplication();
		$cdata = array();
	
		$xml = JFactory::getXML(JPATH_ADMINISTRATOR . '/components/' . $cname . '/' . $name . '.xml');
		$cdata['version'] = (string)$xml->version;
		$jconfig = JFactory::getConfig();
		
		if ( $cname == 'com_easyblog' )
		{
			if ($cdata['version'] < 5)
			{        
				require_once(JPATH_ROOT . '/components/com_easyblog/helpers/helper.php');
				$eb_params = EasyBlogHelper::getConfig();
			}
			else
			{        
				require_once JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/easyblog.php';
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
			
			$profiles = FD::model('profiles');
			
			//print_r($access);die;
			//print_r($access->get('comments'));die;
			//$temp	=	$profiles->getProfiles();
			//$cdata['conversations_limit'] = $es_params->get('conversations')->limit;
			$cdata['activity_limit'] = $es_params->get('activity')->pagination;
			$cdata['lists_limit'] = $es_params->get('lists')->display->limit;
			$cdata['comments_limit'] = $es_params->get('comments')->limit;
			$cdata['stream_pagination_limit'] = $es_params->get('stream')->pagination->pagelimit;
			$cdata['photos_pagination_limit'] = $es_params->get('photos')->pagination->photo;
			$cdata['album_pagination_limit'] = $es_params->get('photos')->pagination->album;
			$cdata['emailasusername'] = $es_params->get('registrations')->emailasusername;
			$cdata['displayName'] = $es_params->get('users')->displayName;
			$cdata['groups']['enabled'] = $es_params->get('groups')->enabled;
			$profiles_data = $profiles->getAllProfiles();

			/* Check for profile_type is allowed for Registration by vivek*/
			$allowed_profile_types = array();

			foreach ($profiles_data as $key)
			{
				if ($key->registration == '1' && $key->state == '1'){
					array_push($allowed_profile_types, $key);
				}
			}
			
			$cdata['profile_types'] = $allowed_profile_types;
		}
		
		return $cdata;
	}
	
	/**
	 * Method get fb plugin config
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getpluginConfig()
	{
		$data = array();
		$plugin = JPluginHelper::getPlugin('api', 'users');
		$pluginParams = new JRegistry($plugin->params);

		$data['fb_login'] = $pluginParams->get('fb_login');
		$data['fb_app_id'] = $pluginParams->get('fb_app_id');
		$data['google_client_id']	=	$pluginParams->get('google_client_id');
		
		return $data;
	}

	/**
	 * Method get joomla config changes
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
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

	public function getProfileACL($userId)
	{

		$access = ES::access($userId,SOCIAL_TYPE_USER);
		$res = new stdClass;

		$res->result->comments = $access->get('comments');
		$res->result->conversations = $access->get('conversations');
		$res->result->events = $access->get('events');
		$res->result->files = $access->get('files');
		$res->result->friends = $access->get('friends');
		$res->result->groups = $access->get('groups');
		$res->result->albums = $access->get('albums');
		$res->result->photos = $access->get('photos');
		$res->result->polls = $access->get('polls');
		$res->result->reports = $access->get('reports');
		$res->result->story = $access->get('story');
		$res->result->stream = $access->get('stream');
		$res->result->videos = $access->get('videos');

		$this->plugin->setResponse($res);
	}
}
