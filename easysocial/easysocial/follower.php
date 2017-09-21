<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugins
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/model.php';

/**
 * API class EasysocialApiResourceFollower
 *
 * @since  1.0
 */
class EasysocialApiResourceFollower extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->getFollowers();
	}

	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->follow();
	}

	/**
	 * Method for follow user
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function follow()
	{
		$app         = JFactory::getApplication();
		$target_user = $app->input->get('target_user', 0, 'INT');
		$type        = $app->input->get('type', 'user', 'STRING');
		$group       = $app->input->get('group', SOCIAL_APPS_GROUP_USER, 'word');
		$log_user    = $this->plugin->get('user')->id;
		$follow      = $app->input->get('follow', 1, 'INT');
		$target      = FD::user($target_user);
		$res         = new stdClass;

		if ($target_user == $log_user)
		{
			$res->result->status = 0;
			$res->result->message = JText::_('PLG_API_EASYSOCIAL_CANT_FOLLOW_YOURSELF_MESSAGE');

			$this->plugin->setResponse($res);
		}

		//  Load subscription table.
		$subscription = FD::table('Subscription');

		/* Get subscription library
		 * $subscriptionLib 	= FD::get('Subscriptions');
		 * $subscriptionLib	= FD::table( 'Subscription' );
		 * Determine if the current user is already a follower
		 * $isFollowing 	= $subscriptionLib->hasFollowed( $target_user, $type, $log_user );
		 */

		$model                 = FD::model('Subscriptions');
		$isFollowing           = $model->isFollowing($target_user, $type . '.' . $group, $log_user);
		$subscription->uid     = $target_user;
		$subscription->type    = $type . '.' . $group;
		$subscription->user_id = $log_user;
		$points                = FD::points();

		if (!$isFollowing)
		{
			$subscriptions = ES::subscriptions();
			$state         = $subscriptions->subscribe($target_user, $type, $group);

			if ($state)
			{
				//  $state = $this->addbadges( $target,$log_user,$subscription->id );
				$res->result->status = 1;
				$res->result->message = JText::_('PLG_API_EASYSOCIAL_FOLLOWING_MESSAGE') . $target->username;

				$this->plugin->setResponse($res);
			}
			else
			{
				$res->result->status = 0;
				$res->result->message = JText::_('PLG_API_EASYSOCIAL_UNABLE_TO_FOLLOW_MESSAGE') . $target->username;

				$this->plugin->setResponse($res);
			}
		}
		else
		{
			$subscriptions = ES::subscriptions();
			$subscriptions->load($target_user, $type, $group, $log_user);

			//  Try to unsubscribe now
			$state = $subscriptions->unsubscribe();

			if ($state)
			{
				/* @points: profile.unfollow
				 * Assign points when user starts new conversation
				 *$points->assign( 'profile.unfollow', 'com_easysocial', $log_user);
				 * @points: profile.unfollowed
				 * Assign points when user starts new conversation
				 * $points->assign( 'profile.unfollowed', 'com_easysocial', $user->id );
				 */
				$res->result->status = 1;
				$res->result->message = JText::_('PLG_API_EASYSOCIAL_SUCCESSFULLY_UNFOLLW_MESSAGE') . $target->username;

				$this->plugin->setResponse($res);
			}
			else
			{
				$res->result->status = 0;
				$res->result->message = JText::_('PLG_API_EASYSOCIAL_UNABLE_TO_UNFOLLW_MESSAGE') . $target->username;

				$this->plugin->setResponse($res);
			}
		}
	}

	/**
	 * Method this common function is for getting dates for month,year,today,tomorrow filters.
	 *
	 * @param   object  $user      user object
	 * @param   int     $log_user  user id
	 * @param   string  $sub_id    subject id
	 * 
	 * @return string
	 *
	 * @since 1.0
	 */
	public function addbadges($user, $log_user, $sub_id)
	{
		$my    = FD::user($log_user);

		//  @badge: followers.follow
		$badge = FD::badges();
		$badge->log('com_easysocial', 'followers.follow', $my->id, JText::_('COM_EASYSOCIAL_FOLLOWERS_BADGE_FOLLOWING_USER'));

		//  @badge: followers.followed
		$badge->log('com_easysocial', 'followers.followed', $user->id, JText::_('COM_EASYSOCIAL_FOLLOWERS_BADGE_FOLLOWED'));

		//  @points: profile.follow
		//  Assign points when user follows another person
		$points = FD::points();
		$points->assign('profile.follow', 'com_easysocial', $my->id);

		//  @points: profile.followed
		//  Assign points when user is being followed by another person
		$points->assign('profile.followed', 'com_easysocial', $user->id);

		// Check if admin want to add stream on following a user or not.
		$config = FD::config();

		if ($config->get('users.stream.following'))
		{
			//  Share this on the stream.
			$stream         = FD::stream();
			$streamTemplate = $stream->getTemplate();

			//  Set the actor.
			$streamTemplate->setActor($my->id, SOCIAL_TYPE_USER);

			//  Set the context.
			$streamTemplate->setContext($sub_id, SOCIAL_TYPE_FOLLOWERS);

			//  Set the verb.
			$streamTemplate->setVerb('follow');

			$streamTemplate->setAccess('followers.view');

			//  Create the stream data.
			$stream->add($streamTemplate);
		}

		//  Set the email options
		$emailOptions = array(
			'title' => 'COM_EASYSOCIAL_EMAILS_NEW_FOLLOWER_SUBJECT',
			'template' => 'site/followers/new.followers',
			'actor' => $my->getName(),
			'actorAvatar' => $my->getAvatar(SOCIAL_AVATAR_SQUARE),
			'actorLink' => $my->getPermalink(true, true),
			'target' => $user->getName(),
			'targetLink' => $user->getPermalink(true, true),
			'totalFriends' => $my->getTotalFriends(),
			'totalFollowing' => $my->getTotalFollowing(),
			'totalFollowers' => $my->getTotalFollowers()
		);
		$state        = FD::notify('profile.followed', array($user->id),
									$emailOptions, array('url' => $my->getPermalink(false, false, false), 'actor_id' => $my->id, 'uid' => $user->id)
								);

		return $state;
	}

	/**
	 * Method Function for delete friend from list 
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function delete()
	{
		$app         = JFactory::getApplication();
		$target_user = $app->input->get('target_userid', 0, 'INT');
		$log_user    = $this->plugin->get('user')->id;

		//  Loads the followers record
		$follower    = FD::table('Subscription');
		$follower->load(array('uid' => $target_user, 'type' => 'user.user', 'user_id' => $log_user));
		$res = new stdClass;

		//  Delete the record
		$res->result = $follower->delete();

		if ($res->result == true)
		{
			$res->status  = 1;
			$res->message = JText::_('PLG_API_EASYSOCIAL_USER_REMOVE_MESSAGE');
		}
		else
		{
			$res->status  = 0;
			$res->message = JText::_('PLG_API_EASYSOCIAL_UNABLE_TO_REMOVE_USER_MESSAGE');
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Method Function use for get friends data
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function getFollowers()
	{
		//  Init variable
		$app                   = JFactory::getApplication();
		$user                  = $this->plugin->get('user')->id;
		$target_user           = $app->input->get('target_user', 0, 'INT');
		$type                  = $app->input->get('type', 'follower', 'STRING');
		$options               = array();
		$options['limitstart'] = $app->input->get('limitstart', 0, 'INT');
		$options['limit']      = $app->input->get('limit', 10, 'INT');

		// Response object
		$res = new stdClass;
		$res->result = array();
		$res->empty_message = '';

		// Load friends model.
		$foll_model = FD::model('Followers');
		$frnd_mod   = FD::model('Friends');

		// Set limitstart
		$foll_model->setUserState('limitstart', $options['limitstart']);

		if (!$target_user)
		{
			$target_user = $user;
		}

		$mapp          = new EasySocialApiMappingHelper;
		$raw_followers = array();

		if ($type == 'following')
		{
			$raw_followers = $foll_model->getFollowing($target_user, $options);
		}
		else
		{
			if ($options['limitstart'] <= $foll_model->getTotalFollowers($target_user))
			{
				$raw_followers = $foll_model->getFollowers($target_user, $options);
			}
		}

		// $frnd_list	=	$this->basefrndObj($ttl_list);
		$fllowers_list = $mapp->mapItem($raw_followers, 'user', $user);

		if (count($fllowers_list) < 1)
		{
			$res->empty_message = JText::_('COM_EASYSOCIAL_NO_FOLLOWERS_YET');

			$this->plugin->setResponse($res);
		}

		/* Get other data
		foreach($fllowers_list as $ky=>$lval)
		{
			$lval->mutual = $frnd_mod->getMutualFriendCount($user,$lval->id);
			$lval->isFriend = $frnd_mod->isFriends($users,$lval->id);
		}*/

		$res->result = $fllowers_list;

		$this->plugin->setResponse($res);
	}
}
