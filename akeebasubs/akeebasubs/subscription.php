<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
require_once JPATH_ROOT . '/components/com_akeebasubs/helpers/email.php';
$jlang = JFactory::getLanguage();


class AkeebasubsApiResourceSubscription extends ApiResource
{

	private $vars = array(
												'firstrun'=>'BOOLEAN',
												'slug'=>'STRING',
												'id'=>'INT',
												'paymentmethod'=>'CMD',
												'processorkey'=>'RAW',
												'username'=>'STRING',
												'password'=>'RAW',
												'password2'=>'RAW',
												'name'=>'STRING',
												'email'=>'STRING',
												'email2'=>'STRING',
												'address1'=>'STRING',
												'address2'=>'STRING',
												'country'=>'STRING',
												'state'=>'STRING',
												'city'=>'STRING',
												'zip'=>'STRING',
												'isbusiness'=>'INT',
												'businessname'=>'STRING',
												'occupation'=>'STRING',
												'vatnumber'=>'STRING',
												'coupon'=>'STRING',
												'custom'=>'RAW',
												'subcustom'=>'RAW',
												'opt'=>'STRING'
											);

	public function post() {

			$input = JFactory::getApplication()->input;
			$user = $this->plugin->getUser();

			if (!$user) {
				$this->plugin->setResponse( $this->getErrorResponse(404, JText::_('JERROR_ALERTNOAUTHOR')) );
				return;
			}

			$authorised = $user->authorise('core.create', 'com_akeebasubs');
			$override_users = $this->getOverrideUsers();
			$is_override = in_array($user->id, array_keys($override_users));
			$allowed = $authorised || $is_override;

			if (!$allowed) {
				$this->plugin->setResponse( $this->getErrorResponse(404, JText::_('JERROR_ALERTNOAUTHOR')) );
				return;
			}

			// Check/create user
			$newuser['name'] = $input->get('name', '', 'STRING');
			$newuser['email'] = $input->get('email', '', 'USERNAME');
			$newuser['username'] = $newuser['email'];

			$subscriber_userid = $this->getSubscriberUserid($newuser);
			if (!$subscriber_userid)
			{
				$this->plugin->setResponse( $this->getErrorResponse(404, 'Problem creating user. Name and email are mandatory. Make sure you use a valid email') );
				return;
			}

			// Create subscription
			$levelid = $input->get('subscription', '', 'INT');
			$enabled = $input->get('enabled', 0, 'INT');
			$state = $enabled ? 'C' : 'N';

			// Do validations
			if ($is_override && !in_array($levelid, $override_users[$user->id]))
			{
				$this->plugin->setResponse( $this->getErrorResponse(404, 'Invalid subscription id') );
				return;
			}

			if (!$this->checkLevel($levelid))
			{
				$this->plugin->setResponse( $this->getErrorResponse(404, 'Invalid subscription id') );
				return;
			}

			$jNow = new JDate();
			$now = $jNow->toUnix();
			$mNow = $jNow->toSql();
			$startDate = $now;
			$nullDate = JFactory::getDbo()->getNullDate();
			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($levelid)
				->getItem();
			if($level->forever)
			{
				$jStartDate = new JDate();
				$endDate = '2038-01-01 00:00:00';
			}
			elseif(!is_null($level->fixed_date) && ($level->fixed_date != $nullDate))
			{
				$jStartDate = new JDate();
				$endDate = $level->fixed_date;
			}
			else
			{
				$jStartDate = new JDate($startDate);

				// Subscription duration (length) modifiers, via plugins
				$duration_modifier = 0;
				JLoader::import('joomla.plugin.helper');
				JPluginHelper::importPlugin('akeebasubs');
				$app = JFactory::getApplication();
				$jResponse = $app->triggerEvent('onValidateSubscriptionLength', array($state));
				if(is_array($jResponse) && !empty($jResponse)) {
					foreach($jResponse as $pluginResponse) {
						if(empty($pluginResponse)) continue;
						$duration_modifier += $pluginResponse;
					}
				}

				// Calculate the effective duration
				$duration = (int)$level->duration + $duration_modifier;
				if ($duration <= 0)
				{
					$duration = 0;
				}

				$duration = $duration * 3600 * 24;
				$endDate = $startDate + $duration;
			}

			$mStartDate = $jStartDate->toSql();
			$jEndDate = new JDate($endDate);
			$mEndDate = $jEndDate->toSql();

			$note = 'Subscription created via API.'.PHP_EOL.'API User : %1$s'.PHP_EOL.'API Userid : %2$s'.PHP_EOL.'Subscriber Name : %3$s'.PHP_EOL.'Subscriber Email : %4$s'.PHP_EOL.'Date : %5$s'.PHP_EOL.'Level id : %6$s';

			$subscription = FOFTable::getInstance('Subscription', 'AkeebasubsTable');
			$subscription->user_id = (int) $subscriber_userid;
			$subscription->akeebasubs_level_id = $levelid;
			$subscription->enabled = $enabled;
			$subscription->state = $state;
			$subscription->processor = 'api';
			$subscription->created_on = $mStartDate;
			$subscription->publish_up = $mStartDate;
			$subscription->publish_down = $mEndDate;
			$subscription->notes = sprintf($note, $user->name, $user->id, $newuser['name'], $newuser['email'], date('Y-m-d H:i:s'), $levelid);

			if ($subscription->store()) {
				// Send email
				//$mailer = AkeebasubsHelperEmail::getPreloadedMailer($subscription, 'plg_akeebasubs_subscriptionemails_new_active');
				//$mailer->addRecipient($newuser['email']);
				//$send = $mailer->Send();

				$this->plugin->setResponse( array('code' => 200, 'id' => $subscription->akeebasubs_subscription_id) );
			} else {
				$this->plugin->setResponse( $this->getErrorResponse(404, 'There was a problem creating the subscription. Make sure you use a valid level id. Name and email are mandatory.') );
			}
	}

	public function get()
	{
	   $this->plugin->setResponse();
	}

	private function getOverrideUsers() {
		$plugin = JPluginHelper::getPlugin('api', 'akeebasubs');
		$params = new JRegistry($plugin->params);

		$users = explode("\n", $params->get('override_create'));

		foreach ($users as $user) {
			$pcs = explode(':', $user);
			$uid = (int) $pcs[0];

			$slugs = explode(',', $pcs[1]);
			$trimmed_slugs = array_map('trim', $slugs);
			$override_users[$uid] = $trimmed_slugs;
		}

		return $override_users;
	}

	private function getSubscriberUserid($newuser) {

		$db = JFactory::getDBO();
		$config = JFactory::getConfig();
		$jlang = JFactory::getLanguage();
		$jlang->load('com_users', JPATH_SITE, 'en-GB', true); // Load English (British)
		$jlang->load('com_users', JPATH_SITE, $jlang->getDefault(), true); // Load the site's default language
		$jlang->load('com_users', JPATH_SITE, null, true); // Load the currently selected language

		$query = $db->getQuery(true);
		$query->select('id');
		$query->from('#__users');
		$query->where('email = ' . $db->Quote($newuser['email']));
		$db->setQuery($query);

		if ($uid = $db->loadResult()) {
			return $uid;
		} else {
			$uid = 0;
			JLoader::import('joomla.application.component.helper');
			$password_clear = JUserHelper::genRandomPassword();
			$salt  = JUserHelper::genRandomPassword(32);
			$crypt = JUserHelper::getCryptedPassword($password_clear, $salt);
			$usersConfig = JComponentHelper::getParams( 'com_users' );
			$defaultUserGroup = $usersConfig->get( 'new_usertype', 2 );

			$password = "$crypt:$salt";
			$instance = JUser::getInstance();
			$instance->set('id'         , 0);
			$instance->set('name'           , $newuser['name']);
			$instance->set('username'       , $newuser['email']);
			$instance->set('password' , $password);
			$instance->set('password_clear' , $password_clear);
			$instance->set('email'          , $newuser['email']);
			$instance->set('usertype'       , 'deprecated');
			$instance->set('groups'     , array($defaultUserGroup));
			// Here is possible set user profile details
			if($instance->save()) {
				$uid = $instance->id;
			}

			$data['fromname'] = $config->get('fromname');
			$data['mailfrom'] = $config->get('mailfrom');
			$data['sitename'] = $config->get('sitename');
			$data['siteurl'] = JUri::root();

			$data = array_merge($newuser, $data);
			$emailSubject = JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			$emailBody = JText::sprintf(
				'COM_USERS_EMAIL_REGISTERED_BODY',
				$data['name'],
				$data['sitename'],
				$data['siteurl'],
				$data['username'],
				$password_clear
			);

			$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

			if ($uid) {
				return $uid;
			} else {
				return false;
			}
		}
	}

	private function checkLevel($level) {
		$akeeba_level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->only_once(1)
			->enabled(1)
			->getItem($level);

		return ($akeeba_level->enabled && $akeeba_level->akeebasubs_level_id == $level);

	}
}
