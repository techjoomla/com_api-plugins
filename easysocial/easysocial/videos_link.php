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
	
	public function get()
	{
		$this->plugin->setResponse($this->get_videos());	
	}
	
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

        $post["category_id"] = $app->input->get('category_id', 0, 'INT');
		$post["title"] = $app->input->get('title', '', 'STRING');
		$post["description"] = $app->input->get('description', '', 'STRING');
		$post["source"] = $app->input->get('source', '', 'STRING');
		$post["link"] = $app->input->get('link', '', 'STRING');
		$post["location"] = $app->input->get('location', '', 'STRING');
		$post["latitude"] = $app->input->get('latitude', '', 'STRING');
		$post["longitude"] = $app->input->get('longitude', '', 'STRING'); 
		$post["privacy"] = $app->input->get('privacy', 'public', 'STRING');
		$post["privacyCustom"] = $app->input->get('privacyCustom', '', 'STRING');
		$post["id"] = $app->input->get('id', 0, 'INT');        

		//$type =  $app->input->get('source','','STRING');		
		//$result = ($type=='link')?$result = $this->processVideo($post):$result = $this->upload_videos($post);

        $crawler = ES::crawler();
		$crawler->crawl($post["link"]);
		$result->data = (object) $crawler->getData();

		$table = ES::table('Video');
		$table->load($id);

		$video = ES::video($table);

		$options = array();

		// Video upload will create stream once it is published.
		// We will only create a stream here when it is an external link.
		if ($post['source'] != SOCIAL_VIDEO_UPLOAD) {
			$options = array('createStream' => true);
		}

		// Save the video
		$state = $video->save($post, $file, $options);

		// Load up the session
		$session = JFactory::getSession();

		if (!$state) {

			// Store the data in the session so that we can repopulate the values again
			$data = json_encode($video->export());

			$session->set('videos.form', $data, SOCIAL_SESSION_NAMESPACE);

			$this->view->setMessage($video->getError(), SOCIAL_MSG_ERROR);
			return $this->view->call(__FUNCTION__, $video, $this->getTask());
		}

		// Once a video is created successfully, remove any data associated from the session
		$session->set('videos.form', null, SOCIAL_SESSION_NAMESPACE);
		$message = 'COM_EASYSOCIAL_VIDEOS_CREATED_SUCCESS';

		if ($id) {
			$message = 'COM_EASYSOCIAL_VIDEOS_UPDATED_SUCCESS';
		}

		$this->view->setMessage($message, SOCIAL_MSG_SUCCESS);

print_r($result);
die("Hello");
	}	

	//upload video in throught api
	public function upload_videos($post)
	{	
			
		// Get the file data
		$file = $app->input->files->get('video');

		if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
			$file = null;
		}
		
		// This video could be edited
		$id = $post["id"];
		$uid = $log_user;
		$type = $app->input->get('type', SOCIAL_TYPE_USER, 'word');


		$table = ES::table('Video');
		$table->load($id);

		$video = ES::video($uid, $type, $table);

		// Determines if this is a new video
		$isNew = $video->isNew();

		// If this is a new video, we should check against their permissions to create
		if (!$video->allowCreation() && $video->isNew()) {
			$res->success = 0;
			$res->message = JText::_('COM_EASYSOCIAL_VIDEOS_NOT_ALLOWED_ADDING_VIDEOS');
		}

		// Ensure that the user can really edit this video
		if (!$isNew && !$video->canEdit()) {
			$res->success = 0;
			$res->message = JText::_('COM_EASYSOCIAL_VIDEOS_NOT_ALLOWED_EDITING');
		}

		$options = array();

		// Video upload will create stream once it is published.
		// We will only create a stream here when it is an external link.
		if ($post['source'] != SOCIAL_VIDEO_UPLOAD) {
			$options = array('createStream' => true);
		}

		// Save the video
		$state = $video->save($post, $file, $options);

		// Load up the session
		$session = JFactory::getSession();

		if($state)
		{
			$res->success = 1;
			$res->message = JText::_('COM_EASYSOCIAL_VIDEOS_UPLOADED_SUCCESS');
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

