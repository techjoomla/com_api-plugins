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
require_once JPATH_SITE.'/components/com_api/models/key.php';
require_once JPATH_SITE.'/components/com_api/models/keys.php';

class UsersApiResourceLogin extends ApiResource
{
	public function get()
	{
		/*$cdata = array();
		//get notification params
		$plugin = JPluginHelper::getPlugin('api', 'easysocial');

		if ($plugin)
		{
		$jparams = new JRegistry($plugin->params);
		$cdata['allow_push_notification'] = $jparams->get('allow_notify');
		$cdata['project_no'] = $jparams->get('project_no');
		$cdata['gcm_server_key'] = $jparams->get('gcm_server_key');
		}
		else
		{	
			$cdata['project_no'] = 0;
			$cdata['message'] = 'Easysocial API plugin not installed';
		}

		$this->plugin->setResponse($cdata);*/
		$this->plugin->setResponse( JText::_( 'PLG_API_USERS_GET_METHOD_NOT_ALLOWED_MESSAGE' ));	
	}

	public function post()
	{
	   $this->plugin->setResponse($this->keygen());
	}

	public function keygen()
	{
		//init variable
		$obj = new stdclass;
		$umodel = new JUser;
		$user = $umodel->getInstance();

		if( !$user->id )
		{
			$user = JFactory::getUser($this->plugin->get('user')->id);
		}

		$kmodel = new ApiModelKey;
		$model = new ApiModelKeys;
		$key = null;
		// Get login user hash
		$kmodel->setState('user_id', $user->id);
		$log_hash = $kmodel->getList();
		$log_hash = $log_hash[count($log_hash) - count($log_hash)];

		if( $log_hash->hash )
		{
			$key = $log_hash->hash;
		}
		elseif( $key == null || empty($key) )
		{
				// Create new key for user
				$data = array(
				'userid' => $user->id,
				'domain' => '' ,
				'state' => 1,
				'id' => '',
				'task' => 'save',
				'c' => 'key',
				'ret' => 'index.php?option=com_api&view=keys',
				'option' => 'com_api',
				JSession::getFormToken() => 1
				);

				$result = $kmodel->save($data);
				$key = $result->hash;
				
				//add new key in easysocial table
				$easyblog = JPATH_ROOT . '/administrator/components/com_easyblog/easyblog.php';
				if (JFile::exists($easyblog) && JComponentHelper::isEnabled('com_easysocial', true))
				{
					$this->updateEauth( $user , $key );
				}
		}
		
		if( !empty($key) )
		{
			$obj->auth = $key;
			$obj->code = '200';
			$obj->id = $user->id;
		
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
			//
		
		}
		else
		{
			$obj->code = 403;
			$obj->message = JText::_('PLG_API_USERS_BAD_REQUEST_MESSAGE');
		}
		return( $obj );
	
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
			
		
		}
		return $cdata;
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
