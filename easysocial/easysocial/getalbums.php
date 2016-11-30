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

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceGetalbums extends ApiResource
{
	public function get()
	{
		$this->plugin->setResponse($this->get_albums());
	}
	//get user album as per id / login user
	public function get_albums()
	{
		$app = JFactory::getApplication();
		
		//getting log_user
		$log_user = $this->plugin->get('user')->id;
		
		//accepting user details.	
		$uid = $app->input->get('uid',0,'INT');
		$type = $app->input->get('type',0,'STRING');	
		$mapp = new EasySocialApiMappingHelper();
		//accepting pagination values.
		$limitstart = $app->input->get('limitstart',5,'INT');
		$limit =  $app->input->get('limit',10,'INT');		
		
		// taking values in array for pagination of albums.		
		//$mydata['limitstart']=$limitstart;
		$mydata['excludeblocked'] = 1;
		$mydata['pagination'] = 1;
		//$mydata['limit'] = $limit;
		$mydata['privacy'] = true;		
		$mydata['order'] = 'a.assigned_date';		
		$mydata['direction'] = 'DESC';		
		//creating object and calling relatvie method for data fetching.
		$obj = new EasySocialModelAlbums();		
		
		//$obj->setState('limitstart',$limitstart);
		//$obj->setState('limit',$limit);
		
		//$obj->limitstart = $limitstart;
		//$obj->limit= $limit;	
	
		// first param is user id,user type and third contains array for pagination.
		$albums = $obj->getAlbums($uid,$type,$mydata);
		//use to load table of album.
		$album = FD::table( 'Album' );
				
		foreach($albums as $album )
		{
			if($album->cover_id)
			{
				$album->load( $album->id );
			}
		$album->cover_featured = $album->getCover('featured');
		$album->cover_large = $album->getCover('large');
		$album->cover_square = $album->getCover('square');
		$album->cover_thumbnail = $album->getCover('thumbnail');         		
		}
		
		//getting count of photos in every albums.	
		foreach($albums as $alb)
		{
		 $alb->count = $obj->getTotalPhotos($alb->id);
		}		
		$all_albums = $mapp->mapItem($albums,'albums',$log_user);
		$output = array_slice($all_albums, $limitstart, $limit);
		return $output;
	}
}	
