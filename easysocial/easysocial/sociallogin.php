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

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE . '/components/com_jfbconnect/libraries/factory.php';
require_once JPATH_ADMINISTRATOR . '/components/com_jfbconnect/assets/facebook-api/facebook.php';
require_once JPATH_ADMINISTRATOR . '/components/com_jfbconnect/models/usermap.php';
require_once JPATH_ADMINISTRATOR . '/components/com_jfbconnect/assets/facebook-api/facebook.php';
require_once JPATH_ADMINISTRATOR . '/components/com_jfbconnect/assets/facebook-api/base_facebook.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';


/**
 * API class EasysocialApiResourceSociallogin
 *
 * @since  1.0
 */
class EasysocialApiResourceSociallogin extends ApiResource
{
	/**	  
	 * GET
	 * 	 
	 * @return  JSON
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getKeys());
	}

	/**	  
	 * POST data
	 * 	 
	 * @return  JSON
	 */
	public function post()
	{
		$app = JFactory::getApplication();
		$provider_nm = $app->input->get('provider', 'facebook', 'CMD');
		$user_id = $app->input->get('user_id', 0, 'INT');
		$tokan = $app->input->get('tokan', 0, 'RAW');
		$email = $app->input->get('email', '', 'STRING');
		$password = $app->input->get('password', '', 'STRING');
		$provider = JFBCFactory::provider($provider_nm);
		$provider->client->authenticate();
		$loginRegisterModel = JFBCFactory::model('LoginRegister');
		$provider->setSessionToken();
		$provider->client->setExtendedAccessToken();
		$provider->onBeforeLogin();
		$config = JFactory::getConfig();
		$lifetime = $config->get('lifetime', 15);
		setcookie('jfbconnect_autologin_disable', 1, time() + ($lifetime * 60));
		$providerUserId = $provider->getProviderUserId();
		$userMapModel = JFBCFactory::usermap();
		$jUserId = $userMapModel->getJoomlaUserId($providerUserId, strtolower($provider->name));
		$jUserEmailId = $userMapModel->getJoomlaUserIdFromEmail($email);

		// Get temp id
		if ($jUserEmailId)
		{
			$providerUserId = $userMapModel->getProviderUserId($jUserEmailId, strtolower($provider->name));
			$jUserId = $userMapModel->getJoomlaUserId($providerUserId, strtolower($provider->name));
		}
		elseif (!$jUserEmailId && JFBCFactory::config()->getSetting('automatic_registration'))
		{
			if (!$jUserEmailId)
			{
				$pdata = array();
				$pdata['email'] = $email;
				$pdata['password'] = $password;
				$fbuser = $loginRegisterModel->createNewUser($provider);
			}

			if ($loginRegisterModel->autoCreateUser($providerUserId, $provider))
			{
				$jUserId = $userMapModel->getJoomlaUserId($providerUserId, strtolower($provider->name));
			}
		}

		$jUser = JUser::getInstance($jUserId);
		$loginSuccess = false;

		// Try to log the user, but not if blocked and initial registration (then there will be a pretty message on how to activate)
		if (!$provider->initialRegistration || ($jUser->get('block') == 0 && $provider->initialRegistration))
		{
			$options = array('silent' => 1, 'provider' => $provider, 'provider_user_id' => $providerUserId);

			// Disable other authentication messages hack for J3.2.0 bug. Should remove after 3.2.1 is available.
			$password = $provider->secretKey;
			$loginSuccess = $app->login(array('username' => $provider->appId, 'password' => $password), $options);
		}

		$this->plugin->setResponse($jUser);
	}

	/**	  
	 * getKeys
	 * 	 
	 * @return  JSON
	 */
	public function getKeys()
	{
		$app	= JFactory::getApplication();
		$jfb_params = JFBCFactory::config()->getSettings();
		$result = array();
		$result['fb_app_id'] = $jfb_params->get('facebook_app_id');
		$result['fb_app_key'] = $jfb_params->get('facebook_secret_key');
		$result['g_app_id'] = $jfb_params->get('google_app_id');
		$result['g_app_key'] = $jfb_params->get('google_secret_key');

		return $result;
	}
}
