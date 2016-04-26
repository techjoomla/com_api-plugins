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
				
	public function get_videos()
	{
		$log_user= $this->plugin->get('user')->id;
		$model = FD::model( 'Videos' );
				
		$result=array();
		$options=array();
		
		$app = JFactory::getApplication();		
		$limitstart=  $app->input->get('limitstart',0,'INT');
		$limit=  $app->input->get('limit',0,'INT');
		$filter = $app->input->get('filter','','STRING');
		$categoryid = $app->input->get('categoryid',0,'INT');
		$sort = $app->input->get('sort','','STRING');
		
		$ordering = $this->plugin->get('ordering', '', 'STRING');
		$userObj = FD::user($log_user);
		
		$options = array('limitstart'=>$limitstart,'limit'=>$limit,'sort'=>$sort,'filter'=>$filter,'category'=>$categoryid,'state' => SOCIAL_STATE_PUBLISHED, 'ordering' => $ordering,'type' => $userObj->isSiteAdmin() ? 'all' : 'user');			
		$data = $model->getVideos($options);		
	
		$mapp = new EasySocialApiMappingHelper();
		$all_videos = $mapp->mapItem($data,'videos',$log_user);
		
		$cats = $model->getCategories();

		foreach($cats as $k=>$row)
		{
			$row->uid = $row->user_id;
		}					
		$result['video'] = $mapp->mapItem($data,'videos',$log_user);
		$result['categories'] = $mapp->categorySchema($cats);
		
		return $result;
	}
	
	public function save_video()
	{          	
		// Check for request forgeries
		//ES::checkToken();

		$app = JFactory::getApplication();
		$res = new stdClass;
		$es_config = ES::config();
		$log_user = $this->plugin->get('user')->id;	
        
		$post['category_id'] = $app->input->get('category_id', 0, 'INT');
        $post['uid'] = $app->input->get('uid', $log_user, 'INT');
   		$post['title'] = $app->input->get('title', '', 'STRING');
        $post['description'] = $app->input->get('description', '', 'STRING');
		$post['link'] =  $app->input->get('path', '', 'STRING');
  		$post['tags'] = $app->input->get('tags', '', 'ARRAY');
        $post['location'] = $app->input->get('location', '', 'STRING');
        $post['privacy'] = $app->input->get('privacy', '', 'STRING');

        $video = ES::video();
        //$video->load($row->id);				
 
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

		if ($id) {
			$message = 'COM_EASYSOCIAL_VIDEOS_UPDATED_SUCCESS';
		}

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
		
		// This video could be edited
		$id = $post["id"];
		$uid = $log_user;
		$type = $app->input->get('type', SOCIAL_TYPE_USER, 'word');

        /*
		// Bind the tags
		if (isset($post['tags'])) {
			$video->insertTags($post['tags']);
		}*/
        

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
        $video->message = JText::_( 'Video uploaded successfully' );	
    	return $video;
		}	
 
		// Determines if the video should be processed immediately or it should be set under pending mode
		if ($es_config->config->get('video.autoencode')) {
			// After creating the video, process it
			$video->process();
		} else {
			// Just take a snapshot of the video
			$video->snapshot();
		}

		$mapp = new EasySocialApiMappingHelper();		
		$video=$mapp->videoMap($video);
		return $res;						
	}

	//function for create new group
	function processVideo($post)
	{	
		//init variable
		$mainframe = JFactory::getApplication();
		$log_user = $this->plugin->get('user')->id;
		// Load the discussion
		$link 	= $mainframe->input->get('link','','STRING');
		$process 	= $mainframe->input->get('process','get','STRING');
		$state 	= $mainframe->input->get('state',1,'INT');
		        
		$result = new stdClass;		
		if($process == 'get' )
		{
			$crawler = ES::crawler();
			$crawler->crawl($link);
			$result->data = (object) $crawler->getData();
		}		
		return $result;	
	}	
}

