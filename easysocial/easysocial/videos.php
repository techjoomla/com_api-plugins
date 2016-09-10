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

class EasysocialApiResourceVideos extends ApiResource
{
	/**
	 * get.
	 *
	 * @see        JController
	 * @since      1.0
	 * @return true or null
	 */
	public function get()
	{
		$this->plugin->setResponse($this->get_videos());	
	}
	
	public function post()
	{       
        	$app = JFactory::getApplication();	
		$type =  $app->input->get('source','upload','STRING');		
		$result = ($type=='link')?$this->processVideo():$this->upload_videos($type);
		$this->plugin->setResponse($result);
	}
				
	public function get_videos()
	{
		$log_user= $this->plugin->get('user')->id;
		$model = FD::model( 'Videos' );
				
		$result = array();
		$options = array();
		$data = array();
		
		$app = JFactory::getApplication();		
		$video_id = $app->input->get('video_id',0,'INT');			
	
				
		$limitstart=  $app->input->get('limitstart',0,'INT');
		$limit=  $app->input->get('limit',0,'INT');
		$filter = $app->input->get('filter','','STRING');
		$categoryid = $app->input->get('categoryid',0,'INT');
		$sort = $app->input->get('sort','latest','STRING');
		
		$ordering = $this->plugin->get('ordering', '', 'STRING');
		$userObj = FD::user($log_user);
		
		$options = array('limitstart'=>$limitstart,'limit'=>$limit,'sort'=>$sort,'filter'=>$filter,'category'=>$categoryid,'state' => SOCIAL_STATE_PUBLISHED, 'ordering' => $ordering); /* ,'type' => $userObj->isSiteAdmin() ? 'all' : 'user' */		
		if($video_id)
		{
			$data[] = $this->getVideoDetails($video_id);
		}
		else
		{	
			if($options['limitstart'] <= $model->getTotalVideos($options))	
			$data = $model->getVideos($options);		
		}
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

	public function getVideoDetails($vid = 0)
	{
		if(!$vid)
		{
			return 0;
		}
		else
		{

			$table = ES::table('Video');
			$table->load($vid);
			return $video = ES::video($table->uid, $table->type, $table);

		}

	}	
	
	//upload video in throught api
	public function upload_videos($type)
	{				
		$app = JFactory::getApplication();
		$res = new stdClass;
		$es_config = ES::config();
		
		$id = $app->input->get('id', 0, 'int');
		$action = $app->input->get('action', '', 'STRING');			
		$table = ES::table('Video');
		$table->load($id);

		$video = ES::video($table->uid, $table->type, $table);

		// Get the callback url
		$callback = $app->input->get('return', '', 'default');
		
		if($action=="featured"){
			$video->setFeatured();
		} elseif($action=="unfeatured") {
			$video->removeFeatured();
		} elseif($action=="delete") {
			$state = $video->delete();
		} elseif($action=="edit") {
			$video 	= FD::table('Video');				
            $video->load($id);	            
            return $video;
		} elseif($action=="update") {
            $video 	= FD::table('Video');				
            $video->load($id);	                    
    		$videoEdit = ES::video($video);

    		// Save the video
            $post['category_id'] = $app->input->get('category_id', 0, 'INT');
            $post['uid'] = $app->input->get('uid', 0, 'INT');
       		$post['title'] = $app->input->get('title', '', 'STRING');
            $post['description'] = $app->input->get('description', '', 'STRING');
    		$post['link'] = $app->input->get('path', '', 'STRING');
      		$post['tags'] = $app->input->get('tags', '', 'ARRAY');
            $post['location'] = $app->input->get('location', '', 'STRING');
            $post['privacy'] = $app->input->get('privacy', '', 'STRING');

		    $state = $videoEdit->save($post);

            $videoEdit->success = 1;
            $videoEdit->message = JText::_( 'Video updated successfully' );	
        	return $videoEdit;
        }										
	}	
}

