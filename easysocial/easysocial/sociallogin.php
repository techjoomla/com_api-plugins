<?php
/**
 * @package	Easysocial API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE.'/components/com_jfbconnect/libraries/factory.php';
require_once(JPATH_ADMINISTRATOR . '/components/com_jfbconnect/assets/facebook-api/facebook.php');
require_once(JPATH_ADMINISTRATOR . '/components/com_jfbconnect/models/usermap.php');
require_once(JPATH_ADMINISTRATOR . '/components/com_jfbconnect/assets/facebook-api/facebook.php');
require_once(JPATH_ADMINISTRATOR . '/components/com_jfbconnect/assets/facebook-api/base_facebook.php');

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';

class EasysocialApiResourceSociallogin extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse('Use post method to login');
	}

	public function post()
	{
		$app = JFactory::getApplication();

		//$type = $app->input->get('type','story','STRING');
		
		$provider_nm = $app->input->get('provider','facebook','CMD');
		$email = $app->input->get('email','','STRING');
		$password = $app->input->get('password','','STRING');
		
		//$log_usr = $this->plugin->get('user')->id;
		
		$provider = JFBCFactory::provider($provider_nm);
		$provider->client->authenticate();
		
		$loginRegisterModel = JFBCFactory::model('LoginRegister');
		
		//$user = jFactory::getUser(161);

		$provider->setSessionToken();
		$provider->client->setExtendedAccessToken();
        
    //print_r($user);die("in api");
        
        //$provider_obj->client->getUser();
        
        $provider->onBeforeLogin();

        $config = JFactory::getConfig();
        $lifetime = $config->get('lifetime', 15);
        setcookie('jfbconnect_autologin_disable', 1, time() + ($lifetime * 60));
       
        $providerUserId = $provider->getProviderUserId();
 
        $userMapModel = JFBCFactory::usermap();
        
        $jUserId = $userMapModel->getJoomlaUserId($providerUserId, strtolower($provider->name));

		$jUserEmailId = $userMapModel->getJoomlaUserIdFromEmail($email);
 
		//get temp id
		if($jUserEmailId)
		{
			$providerUserId = $userMapModel->getProviderUserId($jUserEmailId, strtolower($provider->name));
			$jUserId = $userMapModel->getJoomlaUserId($providerUserId, strtolower($provider->name));
		}
		else if (!$jUserEmailId && JFBCFactory::config()->getSetting('automatic_registration'))
        { 
			//# User is not in system, should create their account automatically
            
            //$jUserEmailId = $userMapModel->getJoomlaUserIdFromEmail($email);
    
			if(!$jUserEmailId)
			{
				$pdata = array();
				$pdata['email'] = $email;
				$pdata['password'] = $password;

				$fbuser = $loginRegisterModel->createNewUser( $provider );
	
			}
     
            if ($loginRegisterModel->autoCreateUser($providerUserId, $provider))
                   $jUserId = $userMapModel->getJoomlaUserId($providerUserId, strtolower($provider->name));
        }


		$jUser = JUser::getInstance($jUserId);
		
		$loginSuccess = false;
        // Try to log the user, but not if blocked and initial registration (then there will be a pretty message on how to activate)
        if (!$provider->initialRegistration || ($jUser->get('block') == 0 && $provider->initialRegistration))
        {
            $options = array('silent' => 1, 'provider' => $provider, 'provider_user_id' => $providerUserId); // Disable other authentication messages
            // hack for J3.2.0 bug. Should remove after 3.2.1 is available.
            $password = $provider->secretKey;

            $loginSuccess = $app->login(array('username' => $provider->appId, 'password' => $password), $options);
        }


		
//print_r( $provider_obj->client->getUser($log_usr) );die("in api");
//$lifetime = $config->set('sef', 45);


		$this->plugin->setResponse($result);
	}
	
}
