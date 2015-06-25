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
	$this->plugin->setResponse($this->get_album_photos());			
	}	
	public function post()
	{
	$this->plugin->setResponse($this->create_album());	
	}
	public function delete()
	{
	$this->plugin->setResponse($this->delete_check());
	}
	//common function to delete photo or album.
	//switch case for photo delete or album delete.
	public function delete_check()
	{
		$app = JFactory::getApplication();
		$flag = $app->input->get('flag',NULL,'STRING');
		switch($flag)
		{        
		case 'deletephoto':	$result1 = $this->delete_photo();
							return $result1;	
		break;							
		case 'deletealbum':	$result = $this->delete_album();
							return $result;
		break;
		}
	}	
	//this function is use to delete photo from album
	public function delete_photo()
	{
		$user = JFactory::getUser($this->plugin->get('user')->id);
		$app = JFactory::getApplication();
		$id = $app->input->get('id',0,'INT');		       
        // Load the photo table
        $photo  = FD::table( 'Photo' );
        $photo->load( $id );		
		$lib = FD::photo( $photo->uid , $photo->type , $photo );		
        if( !$id && !$photo->id )
        {
            return false;
        }
        // Load the photo library
        // Test if the user is allowed to delete the photo
        if( !$lib->deleteable() )
        {
            return false;
        }
        // Try to delete the photo
        $state      = $photo->delete();
        if( !$state )
        {            
            return false;
        }
        else
        return $state;	
	}		
	//this function is used to get the photos from particular album
	public function get_album_photos()
	{
		$app = JFactory::getApplication();
		$album_id = $app->input->get('album_id',0,'INT');
		$uid = $app->input->get('uid',0,'INT');	
		$mapp = new EasySocialApiMappingHelper();
		$log_user= $this->plugin->get('user')->id;
		$limitstart = $app->input->get('limitstart',0,'INT');
		$limit =  $app->input->get('limit',10,'INT');	
		$mydata['album_id']=$album_id;
		$mydata['uid']=$uid;
		$mydata['limitstart']=$limitstart;
		$mydata['limit']=$limit;	
		
		$ob = new EasySocialModelPhotos();
		$ob->setState('limitstart',$limitstart);
		$ob->setState('limit',$limitstart);
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
			
