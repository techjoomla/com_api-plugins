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

defined('_JEXEC') or die('Unauthorized Access');

// We want to import our app library
Foundry::import('admin:/includes/apps/apps');

/**
 * Some application for EasySocial. Take note that all classes must be derived from the `SocialAppItem` class
 *
 * Remember to rename the Textbook to your own element.
 *
 * @since  1.0 
 */
class SocialUserAppPushnotify extends SocialAppItem
{
	/**
	 * Class constructor.
	 *
	 * @since	1.0
	 * @access	public
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Triggers the preparation of stream.
	 *
	 * If you need to manipulate the stream object, you may do so in this trigger.
	 *
	 * @param   object  &$item           The stream object.
	 * @param   bool    $includePrivacy  Determines if we should respect the privacy
	 *
	 * @since   1.0
	 * @access  public
	 *  
	 * @return   string  data
	 */
	public function onPrepareStream(SocialStreamItem &$item, $includePrivacy = true)
	{
		// You should be testing for app context
		if ($item->context !== 'appcontext')
		{
			return;
		}
	}

	/**
	 * Triggers the preparation of activity logs which appears in the user's activity log.
	 *
	 * @param   object  &$item           The stream object.
	 * @param   bool    $includePrivacy  Determines if we should respect the privacy
	 *
	 * @since   1.0 
	 * @return string  data
	 */
	public function onPrepareActivityLog(SocialStreamItem &$item, $includePrivacy = true)
	{
	}

	/**
	 * Triggers after a like is saved.
	 *
	 * @param   string  &$likes  likes
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function onAfterLikeSave(&$likes)
	{
	}

	/**
	 * Triggered when a comment save occurs.
	 *
	 * This trigger is useful when you want to manipulate comments.
	 *
	 * @param   string  &$comment  comment
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function onAfterCommentSave(&$comment)
	{
	}

	/**
	 * Renders the notification item that is loaded for the user.
	 *
	 * This trigger is useful when you want to manipulate the notification item that appears
	 * in the notification drop down.
	 *
	 * @param   string  $item_dt  item
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function onNotificationBeforeCreate($item_dt)
	{
		// Checking table is exists in database then only execute patch.
		$db				=	FD::db();
		$stbl			=	$db->getTableColumns('#__social_gcm_users');
		$reg_ids		=	array();
		$targetUsers	=	array();

		// Create a new query object.
		$query			=	$db->getQuery(true);

		// Select records from the user social_gcm_users table".
		$query->select($db->quoteName(array('device_id', 'sender_id', 'server_key', 'user_id')));
		$query->from($db->quoteName('#__social_gcm_users'));

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$urows			=	$db->loadObjectList();
		$rule			=	$item_dt->rule;

		// Generate element from rule.
		$segments		=	explode('.', $rule);
		$element		=	array_shift($segments);
		$participants	=	$item_dt->participant;
		$emailOptions	=	$item_dt->email_options;
		$systemOptions	=	$item_dt->sys_options;
		$msg_data		=	$this->createMessageData($element, $emailOptions, $systemOptions, $participants);
		$targetUsers	=	$msg_data['tusers'];
		$count			=	rand(1, 100);
		$user			=	FD::user();
		$actor_name		=	$user->username;

		$tit			=	JText::_($msg_data['title']);
		$tit			=	str_replace('{actor}', $actor_name, $tit);

		foreach ($urows as $notfn)
		{
			if (in_array($notfn->user_id, $targetUsers))
			{
				$reg_ids[]	=	$notfn->device_id;
				$server_k	=	$notfn->server_key;
			}
		}

		// Build message for GCM
		if (!empty($reg_ids))
		{
			// Increment counter
			$registatoin_ids	=	$reg_ids;

			// Message to be sent
			$message	=	$tit;

			// Google cloud messaging GCM-API url
			$url 	=	'https://gcm-http.googleapis.com/gcm/send';

			// Setting headers for gcm service.
			$headers	=	array(
							'Authorization' => 'key=' . $server_k,
							'Content-Type' => 'application/json'
							);

			// Setting fields for gcm service. fields contents what data to be sent.
			$fields		=	array(
									'registration_ids' => $registatoin_ids,
									'data' => array(
													"title" => $message,
													"message" => $msg_data['mssge'],
													"notId" => $count,
													"url" => $msg_data['ul'],
													"body" => $msg_data['mssge']
													),
							);

			// Making call to GCM  API using POST.
			jimport('joomla.client.http');

			// Using JHttp for API call
			$http			=	new JHttp;
			$options		=	new JRegistry;

			// $transport	=	new JHttpTransportStream($options);
			$http			=	new JHttp($options);
			$gcmres			=	$http->post($url, json_encode($fields), $headers);
		}
	}

	/**
	 * Method this common function is for getting dates for month,year,today,tomorrow filters.
	 *
	 * @param   array  $element        element
	 * @param   array  $emailOptions   emailoption
	 * @param   array  $systemOptions  systemoption
	 * @param   array  $participants   partition
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function createMessageData($element, $emailOptions, $systemOptions, $participants)
	{
		$data			=	array();

		// Switch case for getting url,avatar of actor,data for particular view

		$emailOptions	=	(is_object($emailOptions))?(array) $emailOptions:$emailOptions;
		$systemOptions	=	(is_object($systemOptions))?(array) $systemOptions:$systemOptions;
		$data['title']	=	$emailOptions['title'];
		$data['title']	=	JText::_($data['title']);
		$data['tusers']	=	$this->createParticipents($participants);

		switch ($element)
		{
			case 'conversations':
								$data['ul']				=	$emailOptions['conversationLink'];
								$data['mssge']			=	$emailOptions['message'];
								$data['authorAvatar']	=	$emailOptions['authorAvatar'];
								$data['actor']			=	$emailOptions['authorName'];
			break;
			case 'friends':
								$data['ul']				=	$emailOptions['params']['requesterLink'];
								$data['authorAvatar']	=	$emailOptions['params']['requesterAvatar'];
								$data['authorlink']		=	$emailOptions['params']['requesterLink'];
								$data['mssge']			=	' ';
								$data['actor']			=	$emailOptions['actor'];
			break;
			case 'profile':
								if ($rulename == 'followed')
								{
									$data['ul']				=	$emailOptions['targetLink'];
									$data['mssge']			=	' ';
									$data['authorAvatar']	=	$emailOptions['actorAvatar'];
								}
								else
								{
									$data['ul']				=	$emailOptions['params']['permalink'];
									$data['authorAvatar']	=	$emailOptions['params']['actorAvatar'];
									$data['mssge']			=	$emailOptions['params']['content'];
									$data['actor']			=	$emailOptions['params']['actor'];
									$data['target_user']	=	$systemOptions['target_id'];
								}
			break;
			case 'likes':
									$data['authorAvatar']	=	$emailOptions['actorAvatar'];
									$data['mssge']			=	' ';
									$data['ul']				=	$emailOptions['permalink'];
									$data['target_link']	=	$emailOptions['targetLink'];
									$data['target_name']	=	$systemOptions['target'];
			break;
			case 'comments':
									$data['authorAvatar']	=	$emailOptions['actorAvatar'];
									$data['mssge']			=	$emailOptions['comment'];
									$data['ul']				=	$emailOptions['permalink'];
									$data['target_link']	=	$emailOptions['targetLink'];
									$data['target_name']	=	$systemOptions['target'];
			break;
			case 'events':
									$data['authorAvatar']	=	(isset($emailOptions['posterAvatar']))?$emailOptions['posterAvatar']:$emailOptions['actorAvatar'];
									$data['mssge']			=	$emailOptions['message'];
									$data['ul']				=	$emailOptions['eventLink'];
									$data['actor']			=	$emailOptions['actor'];

	// This line is for getting event name in notification.
									$data['title']			=	str_replace('{event}', $emailOptions['event'], $data['title']);
			break;
			case 'groups':
									$data['authorAvatar']	=	(isset($emailOptions['posterAvatar']))?$emailOptions['posterAvatar']:$emailOptions['params']->userAvatar;
									$data['mssge']			=	(isset($emailOptions['message']))?$emailOptions['message']:null;
									$data['ul']				=	(isset($emailOptions['groupLink']))?$emailOptions['groupLink']:$emailOptions['params']->groupLink;

									// This line is for getting group name in notification.
									$group_ttl				=	(isset($emailOptions['group']))?$emailOptions['group']:$emailOptions['params']->group;
									$data['title']			=	str_replace('{group}', $group_ttl, $data['title']);
			break;
			case 'stream':
									$data['authorAvatar']	=	$emailOptions['actorAvatar'];
									$data['mssge']			=	$emailOptions['message'];
									$data['ul']				=	$emailOptions['permalink'];
			break;
		}

		return $data;
	}

	/**
	 * Method Create participents unique abjects
	 *
	 * @param   array  $pUsers  array of users
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function createParticipents($pUsers)
	{
		$userObj	=	is_object($pUsers[count($pUsers) - 1]);

		if ($userObj)
		{
			$myarr	=	array();

			foreach ($pUsers as $ky => $row)
			{
				$myarr[]	=	$row->id;
			}

			return $myarr;
		}
		else
		{
			return $pUsers;
		}
	}
}
