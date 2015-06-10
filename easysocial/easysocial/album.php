<?php
/**
 * @package	K2 API plugin
 * @version 1.0
 * @author 	Rafael Corral
 * @link 	http://www.rafaelcorral.com
 * @copyright Copyright (C) 2011 Rafael Corral. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/photos.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/tables/album.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceAlbum extends ApiResource
{
	public function get()
	{
	$this->plugin->setResponse($this->get_album());			
	}	
	public function post()
	{
	$this->plugin->setResponse($this->create_album());	
	}
	public function delete()
	{
	$this->plugin->setResponse($this->delete_album());
	}
	//this function is used to get the photos from particular album
	public function get_album()
	{
		$app = JFactory::getApplication();
		$album_id = $app->input->get('album_id',0,'INT');
		$uid = $app->input->get('uid',0,'INT');	
		$mapp = new EasySocialApiMappingHelper();
		$log_user= $this->plugin->get('user')->id;
		$mydata['album_id']=$album_id;
		$mydata['uid']=$uid;
		
		
		$ob = new EasySocialModelPhotos();
		$photos = $ob->getPhotos($mydata);
		//loading photo table	
		$photo  = FD::table( 'Photo' );
		foreach($photos as $pnode )
		{		 
         $photo->load($pnode->id);
         $pnode->image_large = $photo->getSource('large');
         $pnode->image_square = $photo->getSource('square');
         $pnode->image_thumbnail = $photo->getSource('thumbnail');
         $pnode->image_featured = $photo->getSource('featured');		
		}
		//mapping function
		$all_photos = $mapp->mapItem($photos,'photos',$log_user);		
		return $all_photos;					
	}
	//this function is used to delete photos from album	
	public function delete_album()
	{
		$app = JFactory::getApplication();
		$id = $app->input->get('id',0,'INT');
		$album	= FD::table( 'Album' );
		$album->load( $id );
		if(!$album->id || !$id)
		{
			$result->status=0;
			$result->message='album not exists';
			return $result;			
		}
		else
		{
			$val =$album->delete();	
			$album->assignPoints( 'photos.albums.delete' , $album->uid );
			$val->message = 'album deleted successfully';		
			return $val;
		}
	}	
	//this function is used to create album	
	public function create_album()
	{
		// Get the uid type	and title	
		$app = JFactory::getApplication();
		$uid = $app->input->get('uid',0,'INT');
		$type = $app->input->get('type',0,'USER');	
		$title = $app->input->get('title',0,'USER');		
		// Load the album
		$album	= FD::table( 'Album' );
		$album->load( $post[ 'id' ] );
		// Determine if this item is a new item
		$isNew 	= true;			
		if( $album->id )
		{
			$isNew = false;
		}		
		// Load the album's library
		$lib = FD::albums( $uid , $type );
		// Check if the person is allowed to create albums
		if( $isNew && !$lib->canCreateAlbums() )
		{
			return false;
		}
		// Set the album uid and type
		$album->uid  = $uid;
		$album->type = $type;		
		$album->title = $title;		
		// Determine if the user has already exceeded the album creation
		if( $isNew && $lib->exceededLimits() )
		{	
			return false;
		}
		// Set the album creation alias
		$album->assigned_date 	= FD::date()->toMySQL();
		// Set custom date
		if( isset( $post['date'] ) )
		{
			$album->assigned_date = $post[ 'date' ];
			unset( $post['date'] );
		}		
		// Set the user creator
		$album->user_id	= $uid;
		// Try to store the album
		$state = $album->store();
		// Throw error when there's an error saving album
		if( !$state )
		{			
			return false;
		}
		$photo_obj = new EasySocialApiUploadHelper();
		$photodata = $photo_obj->albumPhotoUpload($uid,$type);				
		$album->params=$photodata;
	return $album;
    }
}	
			
