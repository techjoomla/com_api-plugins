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

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';

class EasysocialApiResourceGetalbums extends ApiResource
{
	public function get()
	{
	$this->plugin->setResponse($this->get_albums());			
	}	
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
		$limitstart = $app->input->get('limitstart',0,'INT');
		$limit =  $app->input->get('limit',0,'INT');		
		// taking values in array for pagination of albums.		
		$mydata['limitstart']=$limitstart;
		$mydata['limit']=$limit;		
		//creating object and calling relatvie method for data fetching.
		$obj = new EasySocialModelAlbums();		
		$obj->setState('limitstart',$limitstart);
		$obj->setState('limit',$limitstart);				
		// first param is user id,user type and third contains array for pagination.
		$albums = $obj->getAlbums($uid,$type,$mydata);
		//use to load table of album.
		$album = FD::table( 'Album' );
				
		foreach($albums as $album )
		{		
         $album->load( $album->cover_id );
         $album->cover_featured = $album->getCover('featured');
         $album->cover_large = $album->getCover('large');
         $album->cover_square = $album->getCover('square');
         $album->cover_thumbnail = $album->getCover('thumbnail');         		
		}
		$all_albums = $mapp->mapItem($albums,'albums',$log_user);		
		return $all_albums;		
	}
}	
