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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/videos.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/components/com_easysocial/controllers/videos.php';

class EasysocialApiResourceVideos_link extends ApiResource
{	
	public function post()
	{
		$this->plugin->setResponse($this->save_video());				        
	}

	public function save_video()
	{          			
		$app = JFactory::getApplication();
		$res = new stdClass;
		$es_config = ES::config();
		$log_user = $this->plugin->get('user')->id;	

		$post['category_id'] = $app->input->get('category_id', 0, 'INT');
		$post['uid'] = $app->input->get('uid', $log_user, 'INT');
		$post['title'] = $app->input->get('title', '', 'STRING');
		$post['description'] = $app->input->get('description', '', 'STRING');
		$post['link'] =  $app->input->get('path', '', 'STRING');
		$post['location'] = $app->input->get('location', '', 'STRING');
		$post['privacy'] = $app->input->get('privacy', '', 'STRING');
       
		$video = ES::video();
		
		if($post['link']) {
			$rx = '~
			^(?:https?://)?              # Optional protocol
			(?:www\.)?                  # Optional subdomain
			(?:youtube\.com|youtu\.be|vimeo\.com)  # Mandatory domain name
			/watch\?v=([^&]+)           # URI with video id as capture group 1
			~x';
			$has_match = preg_match($rx, $post['link'], $matches);
		}		
		$isNew = $video->isNew();

		// Set the current user id only if this is a new video, otherwise whenever the video is edited,
		// the owner get's modified as well.
		if ($isNew) {
			$video->table->user_id = $video->my->id;
		}

		// Video links
		if ($video->table->isLink()) {

			$video->table->path = $post['link'];

			// Grab the video data
			$crawler = ES::crawler();
			$crawler->crawl($video->table->path);

			$scrape = (object) $crawler->getData();

			// Set the video params with the scraped data
			$video->table->params = json_encode($scrape);

			// Set the video's duration
			$video->table->duration = @$scrape->oembed->duration;
			$video->processLinkVideo();
			$video->save($post);
			$video->table->hit();
		}

		// Save the video
		$state = $video->table->store();

		// Bind the video location
		if (isset($post['location']) && $post['location'] && isset($post['latitude']) && $post['latitude'] && isset($post['longitude']) && $post['longitude']) {

			// Create a location for this video
			$location = ES::table('Location');

			$location->uid = $video->table->id;
			$location->type = SOCIAL_TYPE_VIDEO;
			$location->user_id = $video->my->id;
			$location->address = $video['location'];
			$location->latitude = $video['latitude'];
			$location->longitude = $video['longitude'];
			$location->store();
		}
        
		$privacyData = 'public';
		if (isset($post['privacy'])) {

			$privacyData = new stdClass();
			$privacyData->rule = 'videos.view';
			$privacyData->value = $post['privacy'];
			$privacyData->custom = $post['privacyCustom'];

			$video->insertPrivacy($privacyData);
		}

		// check if we should create stream or not.
		$createStream = ($isNew) ? true : false;
		if ($createStream) {
			$video->createStream('create', $privacyData);
		}	
        $video->success = 1;
        $video->message = JText::_( 'COM_EASYSOCIAL_EMAILS_EVENT_NEW_VIDEO' );
        
    	return $video;
	}
}
