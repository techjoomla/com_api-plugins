<?php

/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');

jimport( 'libraries.schema.group' );
jimport( 'joomla.filesystem.file' );

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/fields.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/videos.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/video/video.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/group.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/message.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/discussion.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/stream.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/user.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/profile.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/category.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/albums.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/photos.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/createalbum.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/events.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/videos.php';


class EasySocialApiMappingHelper
{
	public $log_user = 0;
	
	public function mapItem($rows, $obj_type='', $userid = 0 ,$type='', $strip_tags='', $text_length=0, $skip=array()) {
	
		$this->log_user = $userid;

		switch($obj_type)
		{
			case 'category':
						return $this->categorySchema($rows);
						break;
			case 'group':
						return $this->groupSchema($rows,$userid);
						break;
			case 'profile':
						return $this->profileSchema($rows,$userid);
						break;
			case 'fields':
						return $this->fieldsSchema($rows,$userid,$type);
						break;
			case 'user':
						return $this->userSchema($rows);
						break;
			case 'comment':
						return $this->commentSchema($rows);
						break;
			case 'message':
						return $this->messageSchema($rows);
						break;
			case 'conversion':
						return $this->conversionSchema($rows,$userid);
						break;
			case 'reply':
						return $this->replySchema($rows);
						break;
			case 'discussion':
						return $this->discussionSchema($rows);
						break;
			case 'stream':
						return $this->streamSchema($rows,$userid);
						break;
			case 'albums':
						return $this->albumsSchema($rows,$userid);
						break;			
			case 'photos':
						return $this->photosSchema($rows,$userid);
						break;
			case 'event':
                        return $this->eventsSchema($rows,$userid);                                                
                        break;
            case 'videos':
                        return $this->videosSchema($rows,$userid);                                                
                        break;
            case 'polls':
						return $this->pollsSchema($rows);                                                
                        break;
		}
		
		return $item;
	}
	//To build photo object 
	public function photosSchema($rows,$userid)
	{
		$lang = JFactory::getLanguage();
		$lang->load('com_easysocial', JPATH_ADMINISTRATOR, '', true);
		$result = array();		
		foreach($rows as $ky=>$row)
		{

			if(isset($row->id))
			{					
				$item = new PhotosSimpleSchema();
				$pht_lib = FD::photo($row->id,$row->type,$row->album_id);
	
				$item->isowner= ( $pht_lib->creator()->id == $userid )?true:false;			
				$item->id = $row->id;
				$item->album_id = $row->album_id;
				$item->cover_id = $row->cover_id;
				$item->type = $row->type;
				$item->uid = $row->id; // for post comment photo id is required.
				$item->user_id = $row->user_id;
				$item->title = JText::_($row->title);
				$item->caption= JText::_($row->caption);
				$item->created=$row->created;				
				$item->state=$row->state;
				$item->assigned_date=$row->assigned_date;
				$item->image_large=$row->image_large;
				$item->image_square=$row->image_square;				
				$item->image_thumbnail=$row->image_thumbnail;				
				$item->image_featured=$row->image_featured;
				$like = FD::photo($row->id);

				$like->data->id=$row->id;
				$data = $like->likes();				
				$item->likes=$this->createlikeObj($data,$userid);
				$comobj = $like->comments();
				//$comobj->stream_id=1;

				$item->comment_element = $comobj->element.".".$comobj->group.".".$comobj->verb;			
				$item->comments=$this->createCommentsObj($comobj);

				$result[] = $item;
			}
		}
		return $result;
	}
	//to build ablum object
	public function albumsSchema($rows,$userid)	
	{	
		//To load easysocial language constant
		$lang = JFactory::getLanguage();
		$lang->load('com_easysocial', JPATH_ADMINISTRATOR, '', true);
		$result = array();
		
		foreach($rows as $ky=>$row)
		{	
			if(isset($row->id))
			{
				$item = new GetalbumsSimpleSchema();
				
				$item->id = $row->id;
				$item->cover_id = $row->cover_id;
				$item->type = $row->type;
				$item->uid = $row->uid;
				$item->title = JText::_($row->title);
				$item->caption= JText::_($row->caption);
				$item->created=$row->created;
				$item->assigned_date=$row->assigned_date;
				$item->cover_featured=$row->cover_featured;
				$item->cover_large=$row->cover_large;
				$item->cover_square=$row->cover_square;
				$item->cover_thumbnail=$row->cover_thumbnail;
				$item->count=$row->count;				
				$likes = FD::likes($row->id, SOCIAL_TYPE_ALBUM , 'create', SOCIAL_APPS_GROUP_USER );				
				$item->likes = $this->createlikeObj($likes,$userid);
			    //$item->total=$item->likes->total;			
				
				// Get album comments
				$comments = FD::comments($row->id, SOCIAL_TYPE_ALBUM , 'create', SOCIAL_APPS_GROUP_USER , array('url' => $row->getPermalink()));				
				$item->comment_element = $comments->element.".".$comments->group.".".$comments->verb;				
				if(!$comments->stream_id)
				$comments->element=$item->comment_element;

				
			
				//$comments->element=$item->comment_element;				
				$item->comments = $this->createCommentsObj($comments);				
				$options = array('uid' => $comments->uid, 'element' => $item->comment_element, 'stream_id' => $comments->stream_id);				
				$item->comments['base_obj']->element="albums";						
				$model  = FD::model('Comments');
				$comcount = $model->getCommentCount($options);	
				//code edit for comment count 
				$item->total=$comcount;
				$item->comments['total']=$comcount;
				
				$item->isowner = ( $row->uid == $userid )?true:false;				
				$result[] = $item;
			}
		}
		return $result;		
	}
	
	//To build field object
	public function fieldsSchema($rows,$userid,$type)
	{
		if(empty($rows))
		{
		  return array();	
		}
		
		$lang = JFactory::getLanguage();
		$lang->load('com_easysocial', JPATH_ADMINISTRATOR, '', true);
		//$str = JText::_('COM_EASYSOCIAL_FIELDS_PROFILE_DEFAULT_DESIRED_USERNAME');
		
		$user = FD::user($userid);
		
		if(count($rows)>0)
		{
			$data = array();
			$fmod_obj = new EasySocialModelFields();
			foreach($rows as $row)
			{
//print_r($row);die("in map");
				$fobj = new fildsSimpleSchema();
				
				//$fobj->id = $row->id;
				$fobj->field_id = $row->id;
				$fobj->unique_key = $row->unique_key;
				//$fobj->title = JText($row->title);
				$fobj->title = JText::_($row->title);
				$fobj->field_name = JText::_($row->title);
				$fobj->step = $row->step_id;
				$fobj->field_value = $fmod_obj->getCustomFieldsValue($row->id,$userid, $type);
				
				if($fobj->field_name == 'Name' &&  $fobj->field_value != null )
				{
					
					$fobj->field_value = $user->name;

 					//$fobj->field_value = $gender->data;
					//$fobj->field_value = ( $gender->data == 1 )?'male':'female';
				}
				
				if($fobj->field_name == 'Gender' &&  $fobj->field_value != null )
				{
					
					$gender = $user->getFieldValue('GENDER');
 					//$fobj->field_value = $gender->data;
					$fobj->field_value = ( $gender->data == 1 )?'male':'female';
				}
				
				/*if($fobj->field_name == 'Birthday')
				{
					$birthday = $user->getFieldValue('BIRTHDAY');
					$date = new DateTime($birthday->data);	
					$fobj->field_value = $date->format('d-m-Y');

				}*/

			// Rework on this work
				if($fobj->field_name == 'Birthday')
				{
					if($fobj->field_value == "Invalid Date")
					{
						$fobj->field_value = "";
					}
					else
					{
						$birthday = $user->getFieldValue('BIRTHDAY');
						$date = new DateTime($birthday->data);	
						$fobj->field_value = $date->format('d-m-Y');						
					}
				}
				if($fobj->field_name == 'Website' &&  $fobj->field_value != null)
				{
					$fobj->field_value = '<a href="'.$fobj->field_value.'">'.$fobj->field_value.'</a>';
				}
				
				//to manage address as per site
				/*if( $fobj->unique_key == 'ADDRESS' )
				{
					//$fobj->field_value = $row->data['address'];
					$fobj->field_value = $row->data['state'].','.$row->data['country'];
				}*/

				//to manage relationship
				if(isset($rs_vl->type) && $fobj->unique_key == 'RELATIONSHIP' )
				{
					$rs_vl = json_decode($fobj->field_value);
					$fobj->field_value = $rs_vl->type;
				}
				//vishal - runtime solution for issue, need rework
				if (preg_match('/[\'^[]/', $fobj->field_value))
				{
					$fobj->field_value = implode(" ",json_decode($fobj->field_value));
				}

				//Vishal - code for retrive checkbox value - need ES code 
				if( $row->element == 'checkbox')
				{

					//$fldModel = FD::model('fields');
					//$fldModel->load($fobj->field_id);
					//$fopt = $fldModel->getOptions($fobj->field_id);
			
					$uval = explode(' ',$fobj->field_value);

					$oarr = array();
					$ftbl = FD::table('field');
					$ftbl->load($fobj->field_id);
					$options = $ftbl->getOptions();


					//retrive selected option title
					foreach($options['items'] as $item)
					{
	
						if(in_array(ucfirst($item->value),$uval))
						{
							$oarr[] = $item->title;
						}

					}

					$fobj->field_value = implode(',',$oarr);

				}
				//end						
		
				
				$fobj->params = json_decode($row->params);
				
				$data[] = $fobj; 
			}
			
			return $data;
		}
	}
	//function for stream main obj
	public function streamSchema($rows,$userid) 
	{
		$result = array();
		if(is_array($rows) && empty($rows))
		{
		  return $result;	
		}		

		foreach($rows as $ky=>$row)
		{
			if(isset($row->uid))
			{

				$item = new streamSimpleSchema();

				//new code
				// Set the stream title
				$item->id = $row->uid;
				
				//$item->title = strip_tags($row->title);
				//code changed as request not right way
				$item->title = $row->title;
				if($row->type != 'links')
				{
					$item->title = str_replace('href="','href="'.JURI::root(),$item->title);
				}
	
				$item->type = $row->type;
				$item->group = $row->cluster_type;
				$item->element_id = $row->contextId;
				//
				$item->content = urldecode(str_replace('href="/index','href="'.JURI::root().'index',$row->content));
		
				//$item->preview = $row->preview;
				
				//hari - code for build video iframe
				//check code optimisation
				$frame_match= preg_match('/;iframe.*?>/', $row->preview);
				   if($frame_match)
				   {
						$dom = new DOMDocument('1.0', 'UTF-8');
						//handle error level
						$internalErrors = libxml_use_internal_errors(true);

						$dom->loadHTML($row->preview);
						// Restore error level
						libxml_use_internal_errors($internalErrors);
					 
					   foreach ($dom->getElementsByTagName('a') as $node) {
							   $first = $node->getAttribute( 'href' );                                        
							   break;                                
					   }
					   if(strstr($first,"youtu.be"))        
					   {
							   $first=preg_replace("/\s+/",'',$first);
							   $first=preg_replace("/youtu.be/","youtube.com/embed",$first);                                        
							   $abc=$first."?feature=oembed";
							   $item->preview ='<div class="video-container"><iframe src="'.$abc.'" frameborder="0" allowfullscreen=""></iframe></div>';
					   }                                        
					   else
					   {
					   $df=preg_replace("/\s+/",'',$first);                                        
					   $df=preg_replace("/watch\?v=([a-zA-Z0-9\]+)([a-zA-Z0-9\/\\\\?\&\;\%\=\.])/i","embed/$1 ",$first);
					   $abc=$df."?feature=oembed";
					   $df=preg_replace("/\s+/",'',$abc);
					   $item->preview ='<div class="video-container"><iframe src="'.$df.'" frameborder="0" allowfullscreen=""></iframe></div>';        
					   }
				   }
				   else
				   {
						   $item->preview = $row->preview;
				   }
				//end
				
				// Set the stream content
				if(!empty($item->preview))
				{
					$item->raw_content_url = $row->preview;
				}
				elseif(!empty($item->content))
				{
					$item->raw_content_url = $row->content;
				}
				
				if($row->type != 'links')
				{				
					$item->raw_content_url = str_replace('href="/','href="'.JURI::root(),$item->raw_content_url);
					$item->content = str_replace('href="/','href="'.JURI::root(),$item->content);
				
				}
				// Set the publish date
				$item->published = $row->created->toMySQL();
				
				/*
				// Set the generator
				$item->generator = new stdClass();
				$item->generator->url = JURI::root();

				// Set the generator
				$item->provider = new stdClass();
				$item->provider->url = JURI::root();
				*/
				// Set the verb
				$item->verb = $row->verb;

				//create users object
				$actors = array();
				$user_url = array();
				foreach($row->actors as $actor)
				{
					$user_url[$actor->id] = JURI::root().FRoute::profile( array('id' => $actor->id , 'layout' => 'item', 'sef' => false ));
					$actors[] = $this->createUserObj($actor->id); 
				}
				
				//with share obj users object
				//$with_usr = array();
				$with_user_url = array();
				
				foreach($row->with as $actor)
				{
					$withurl = JURI::root().FRoute::profile( array('id' => $actor->id , 'layout' => 'item', 'sef' => false ));
					$with_user_url[] = "<a href='".$withurl."'>".$this->createUserObj($actor->id)->display_name."</a>";
					//$with_url = $with_url." and ".
					
					//$with_user_url[] = $this->createUserObj($actor->id); 
				}
				$item->with = null;
				//to maintain site view for with url
				if( !empty($with_user_url) )
			   {
				   $cnt = sizeof($with_user_url);                                                                                
				   $item->with = 'with '.$with_user_url[0];
																				   
				   for($i=0;$i<$cnt-2;$i++)
				   {                                                
						   $item->with = $item->with.', '.$with_user_url[$i+1];                                        
				   }
				   if($cnt-1 != 0)
				   {
						   $item->with =  $item->with.' and '.$with_user_url[$cnt-1];                                        
				   }  
			   }
				
                //
				$item->actor = $actors;
				//This node is for Report-flag for the posts.
				$item->isself = ( $actors[0]->id == $userid )?true:false;

				$item->likes = (!empty($row->likes))?$this->createlikeObj($row->likes,$userid):null;
				
				if(!empty($row->comments->element))
				{
					$item->comment_element = $row->comments->element.".".$row->comments->group.".".$row->comments->verb;
					$row->comments->stream_id = $row->comments->options['streamid'];
				}
				else
				{
					$item->comment_element = null;
				}
				
				$item->comments = (!empty($row->comments->uid))?$this->createCommentsObj($row->comments):null;
				
				// These properties onwards are not activity stream specs
				//$item->icon = $row->fonticon;

				// Set the lapsed time
				$item->lapsed = $row->lapsed;

				// set the if this stream is mini mode or not.
				// mini mode should not have any actions such as - likes, comments, share and etc.
				$item->mini = $row->display == SOCIAL_STREAM_DISPLAY_MINI ? true : false;
				
				//build share url use for share post through app
				$sharing = FD::get( 'Sharing', array( 'url' => FRoute::stream( array( 'layout' => 'item', 'id' => $row->uid, 'external' => true, 'xhtml' => true ) ), 'display' => 'dialog', 'text' => JText::_( 'COM_EASYSOCIAL_STREAM_SOCIAL' ) , 'css' => 'fd-small' ) );
				$item->share_url = $sharing->url;
				
				// Check if this item has already been bookmarked
				$sticky = FD::table('StreamSticky');
				$item->isPinned = null;
				if($sticky)
				{	
					$item->isPinned = $sticky->load(array('stream_id' => $row->uid));
				}
				
				//create urls for app side mapping
				//$log_usr = FRoute::profile( array('id' => $row->uid , 'layout' => 'item', 'sef' => false ));
				$strm_urls = array();
				
				$strm_urls['actors'] = $user_url;
				
				if($row->type == 'polls')
				{
					$pdata = json_decode($row->params)->poll;
					
					$item->content = $this->createPollData($pdata->id);
				}

if($item->id == 11115)
{		
//print_r(JPATH_SITE);die("in api");
}
								
				$result[]	= $item;
				//$result[]	= $row;
				//end new
			
			}
		}

		return $result;
	}
	
	//create like object
	public function createLikeObj($row,$userid)
	{
		$likesModel = FD::model('Likes');
		if (!is_bool($row->uid)) {
	
			// Like id should contain the exact item id
			$item = new likesSimpleSchema();
			
			$key = $row->element.'.'.$row->group.'.'.$row->verb;

			$item->uid = $row->uid;
			$item->element = $row->element;
			$item->group = $row->group;
			$item->verb = $row->verb;
			
			$item->hasLiked = $likesModel->hasLiked($row->uid,$key,$userid,$row->stream_id);
			$item->stream_id = $row->stream_id;

			// Get the total likes
			$item->total = $likesModel->getLikesCount($row->uid, $key);
			$item->like_obj = $likesModel->getLikes($row->uid,$key);
			
			return $item;
		}
		return null;
	}
	
	//to build poll content 
	public function createPollData($pid)
	{
		//$pdata = json_decode($row->params)->poll;
		$poll = FD::table( 'Polls' );
		$poll->load($pid);
		$opts = $poll->getItems();
		$pollLib = FD::get('Polls');
	//~ print_r($poll);
	//~ die('In map');	
		//new code 
		$my = ES::user();
		$privacy = $my->getPrivacy();

		$isVoted = $poll->isVoted($my->id);

		$isExpired = false;
		$showResult = false;
		$canVote = false;
		$canEdit = ($my->id == $poll->created_by || $my->isSiteAdmin()) ? true : false;

		if ($privacy->validate('polls.vote', $poll->created_by, SOCIAL_TYPE_USER) ) {
			$canVote = true;
		}

		// check if user has the access to vote on polls or not.
		if ($canVote) {
			$access = $my->getAccess();
			if (! $access->allowed('polls.vote')) {
				$canVote = false;
			}
		}

		if ($poll->expiry_date && $poll->expiry_date != '0000-00-00 00:00:00') {
			// lets check if this poll already expired or not.
			$curDateTime = ES::date()->toSql();

			if ($curDateTime >= $poll->expiry_date) {
				$canVote = false;
				$isExpired = true;
			}
		}

		if ($isVoted || !$canVote) {
			$showResult = true;	}
			
		$poll->isExpired = $isExpired;
		$poll->canVote = $canVote;

		$content = $this->createPollDataview($poll, $opts);
		return $content;	
	}
	
	public function createPollDataview($poll, $opts)
	{
		//new code end
		$content = "<div ng-disabled=".!$poll->canVote.">";
		
		$content = "<label>".$poll->title."</label>";
		  $check = array();
		foreach($opts as $k=>$val)
		{
				  $check[] = $val->id;		
		}
		$arr = count($check);
		$check = implode(':',$check);
		
		//var_dump($check);die("in api");
		$obj = new stdClass();
		$obj->poll = $poll;
		$obj->opts = $opts;
		
		return $content  = $obj;
		
	}
	
	
	//server date offset setting
	public function getOffsetServer($date,$userid)
	{
			
		/*$date = new DateTime($date);
		$config = JFactory::getConfig();
		$date->setTimezone(new DateTimeZone($config->get('offset')));
		$date =  $date->format('Y-m-d H:i:s a');*/
		
		$config = JFactory::getConfig();
		$user   = JFactory::getUser($userid);
               	$offset = $user->getParam('timezone', $config->get('offset'));

		if (!empty($date) && $date != '0000-00-00 00:00:00')
		{
		       $udate = JFactory::getDate($date, $offset);
		       //$date = $udate->toSQL();
			$date =  $udate->format('Y-m-d H:i:s a');
		}

               return $date;

	}			

	//create comments object
	public function createCommentsObj($row,$limitstart=0,$limit=10)
	{

		if (!is_bool($row->uid))
		{
			$options = array('uid' => $row->uid, 'element' => $row->element, 'stream_id' => $row->stream_id, 'start' => $limitstart, 'limit' => $limit);

			$model  = FD::model('Comments');

			$result = $model->getComments($options);

			$data = array();
			$data['total'] = 0;
			$data['base_obj'] = $row;
			
			$likesModel = FD::model('Likes');
			
			foreach($result As $cdt)
			{
				$item = new commentsSimpleSchema();
				
				$row->group = (isset($row->group))?$row->group:null;
				$row->verb = (isset($row->group))?$row->verb:null;

				$item->uid = $cdt->id;
				$item->element = $cdt->element;
				$item->element_id = $row->uid;
				$item->stream_id = $cdt->stream_id;
				$item->comment = $cdt->comment;
				$item->type = $row->element;
				$item->verb = $row->verb;
				$item->group = $row->group;
				$item->created_by = $this->createUserObj($cdt->created_by);
				$item->created = $cdt->created;

				$item->likes   = new likesSimpleSchema();
				$item->likes->uid     = $cdt->id;
				$item->likes->element = 'comments';
				$item->likes->group   = 'user';
				$item->likes->verb    = 'like';
				$item->likes->stream_id = $cdt->stream_id;
				$item->likes->total   = $likesModel->getLikesCount($item->uid, 'comments.' . 'user' . '.like');
				$item->likes->hasLiked = $likesModel->hasLiked($item->uid,'comments.' . 'user' . '.like',$cdt->created_by);
				$data['data'][] = $item;
			}
			
			//$data['total'] = count($data['data']);
			$comcount = $model->getCommentCount($options);        
                    
                       	$data['total']=$comcount;
			return $data;
		}
		
		return null;
	}
	
	//function for discussion main obj
	public function discussionSchema($rows) 
	{
		//$conv_model = FD::model('Conversations');
		$result = array();

		foreach($rows as $ky=>$row)
		{
			if(isset($row->id))
			{

				$item = new discussionSimpleSchema();

				$item->id = $row->id;
				$item->title = $row->title;
				$item->description = $row->content;
				//$item->attachment = $conv_model->getAttachments($row->id);
				$item->created_by = $this->createUserObj($row->created_by);
				$item->created_date = $this->dateCreate($row->created);
				$item->lapsed = $this->calLaps($row->created);
				$item->hits = $row->hits;
				$item->replies_count = $row->total_replies;
				$item->last_replied = $this->calLaps($row->last_replied);
				
				//$item->replies = 0;
				$last_repl = (isset($row->lastreply))?array(0=>$row->lastreply):array();
				
				$item->replies = $this->discussionReply($last_repl);
				
				$result[] = $item;
			}
		}

		return $result;
	}
	
	//function for discussion reply obj
	public function discussionReply($rows) 
	{
		if(empty($rows))
		return 0;
		//$conv_model = FD::model('Conversations');
		$result = array();
		
		foreach($rows as $ky=>$row)
		{
			if(isset($row->id))
			{
				$item = new discussionReplySimpleSchema();

				$item->id = $row->id;
				$item->reply = $row->content;
				$item->created_by = $this->createUserObj($row->created_by);
				$item->created_date = $this->dateCreate($row->created);
				$item->lapsed = $this->calLaps($row->created);
				
				$result[] = $item;
			}
		}

		return $result;
	}
	
	//function for create category schema
	public function categorySchema($rows) 
	{
		$result = array();
		
		foreach($rows as $ky=>$row)
		{
			if(isset($row->id))
			{
				$item = new CategorySimpleSchema();

				$item->categoryid = $row->id;
				$item->title = $row->title;
				$item->description = $row->description;
				$item->state = $row->state;
				//$item->attachment = $conv_model->getAttachments($row->id);
				$item->created_by = $this->createUserObj($row->uid);
				$item->created_date = $this->dateCreate($row->created);

				$result[] = $item;
			}
		}
		
		return $result;
	}
	//to build event obj.
	public function eventsSchema($rows,$userid)
	{
		$lang = JFactory::getLanguage();
		$lang->load('com_easysocial', JPATH_ADMINISTRATOR, '', true);
		$result = array();		
		foreach($rows as $ky=>$row)
		{
			if(isset($row->id))
			{	
				$item = new EventsSimpleSchema();
				$item->id=$row->id;
				$item->title=$row->title;
				$item->description=$row->description;
				//getting all event images
				foreach($row->avatars As $ky=>$avt)
				{
					$avt_key = 'avatar_'.$ky;
					$item->$avt_key = JURI::root().'media/com_easysocial/avatars/event/'.$row->id.'/'.$avt;
										
					$fst = JFile::exists('media/com_easysocial/avatars/event/'.$row->id.'/'.$avt);
					//set default image
					if(!$fst)
					{
						$item->$avt_key = JURI::root().'media/com_easysocial/defaults/avatars/event/'.$ky.'.png';
					}
				}
				//end
			
				$item->params=json_decode($row->params);

				$item->details=$row->meta;
				//ios format date
				if(!empty($item->details->start))
				{					
					$item->details->ios_start = $this->listDate($item->details->start);
					$item->start_date = date('D M j Y h:i a',strtotime($row->meta->start));					
					$item->start_date_unix = strtotime($row->meta->start);
				}								
				

				if( $item->details->end == "0000-00-00 00:00:00")
				{
					$item->details->ios_end = null;
                    		$item->end_date = null;
					$item->end_date_unix = null;
				
				/*	
					$item->details->ios_end = $this->listDate($item->details->start);
					$item->end_date = date('D M j Y h:i a',strtotime($row->meta->start));					
					$item->end_date_unix = strtotime($row->meta->start);
				*/
				
				}
				else				
				{
					$item->details->ios_end = $this->listDate($item->details->end);
                               		$item->end_date = date('D M j Y h:i a ',strtotime($row->meta->end));
					$item->end_date_unix = strtotime($row->meta->end);
				}
				//ios format date					
				//$item->start_date_ios = $this->listDate($row->meta->start);
				//$item->end_date_ios = $this->listDate($row->meta->end);
				
				$item->start_date_unix = strtotime($row->meta->start);
				$item->end_date_unix = strtotime($row->meta->end);
				
				$event = FD::model( 'events' );
				$item->guests= $event->getTotalAttendees($row->id);
				
				$item->featured=$row->featured;
				$item->created=$row->created;
				$item->categoryId=$row->category_id;
				$item->type=$row->type;
			
				//get category name
				$category 	= FD::table('EventCategory');
				$category->load($row->category_id);				
				$item->category_name = $category->get('title');
				
				//event guest status
				$eventobj=FD::event($row->id);	
				$item->isAttending=$eventobj->isAttending($userid);
				$item->isNotAttending=$eventobj->isNotAttending($userid);
				$item->isOwner=$eventobj->isOwner($userid);
				$item->isPendingMember = $eventobj->isPendingMember($userid);
				$item->isMember=$eventobj->isMember($userid);	
				$item->isRecurring=$eventobj->isRecurringEvent();                                
                               	$item->hasRecurring=$eventobj->hasRecurringEvents();				
				
				$event_owner = reset($row->admins);
                             	if($event_owner)
				{
                		$item->owner = $this->createUserObj($event_owner)->username;
				$item->owner_id = $event_owner;
				}                		
				//$item->owner=$user->username;
				
				$item->isMaybe=in_array($userid,$row->maybe);
				$item->total_guest=$eventobj->getTotalGuests();
				// this node is for past events
                $item->isoverevent=$eventobj->isOver();
                if($item->end_date == null){
					$item->isoverevent = false;
				}
				
				$item->location=$row->address;
				$item->longitude=$row->longitude;
				$item->latitude=$row->latitude;
				$NameLocationLabel = $item->location;
				$item->event_map_url_andr  =  "geo:".$item->latitude.",".$item->longitude."?q=".$NameLocationLabel;
                		//$item->event_map_url_ios = "geo:".$item->latitude.",".$item->longitude."?q=".$NameLocationLabel;
				$item->event_map_url_ios = "http://maps.apple.com/?q=".$NameLocationLabel."&sll=".$item->latitude.",".$item->longitude;				
				$item->share_url = JURI::root().$eventobj->getPermalink(true, false, 'item', false);
				//getting cover image of event
				$eve = FD::table( 'Cover' );
				$eve->type='event';
				$eve->photo_id=$row->cover->photo_id;
				$item->cover_image=$eve->getSource();
				//end
                $item->isInvited = false;
                $event = FD::event($row->id);
				$guest = $event->getGuest($userid);
				if ($guest->invited_by) {	
					$item->isInvited = true;
				}
				$result[] = $item;
			}
		}
		return($result);	
	}
	
	//function for create group schema
	public function groupSchema($rows=null,$userid=0) 
	{
		if($rows == null || $userid == 0)
		{
			$ret_arr = new stdClass;
			$ret_arr->status = false;
			$ret_arr->message = JText::_('PLG_API_EASYSOCIAL_GROUP_NOT_FOUND');
			
			return $ret_arr;
		}

		$result = array();
		$user = JFactory::getUser($userid);
		$user1 = FD::user($userid);
		//easysocial default profile
		$profile = $user1->getProfile();
		foreach($rows as $ky=>$row)
		{
			$fieldsModel = FD::table('FieldData');
			$fieldsModel->load($row->id);
			
			$stepsModel = FD::model('Steps');
			$steps = $stepsModel->getSteps($row->category_id, SOCIAL_TYPE_CLUSTERS, SOCIAL_PROFILES_VIEW_DISPLAY);
			
			// Get custom fields model.
			$fieldsModel = FD::model('Fields');
			// Get custom fields library.
			$fields = FD::fields();
			$field_arr = array();
			foreach ($steps as $step)
			{

				$step->fields = $fieldsModel->getCustomFields(array('step_id' => $step->id, 'data' => true, 'dataId' => $userid, 'dataType' => SOCIAL_TYPE_GROUP, 'visible' => SOCIAL_GROUPS_VIEW_DISPLAY));
				$fields = null;
				
				if(count($step->fields))
				{
					$fields = $this->fieldsSchema($step->fields,$row->id,SOCIAL_FIELDS_GROUP_GROUP);
					//die("in fields loop");
					if(empty($field_arr))
					{
						$field_arr = $fields;
					}
					else
					{
						foreach($fields as $fld)
						{
							array_push( $field_arr,$fld );
						}
						//array_merge( $field_arr,$fields );
					}
				}
			}
			
			if(isset($row->id))
			{
				$grpobj = FD::group( $row->id );
				$item = new GroupSimpleSchema();
				
				$item->id = $row->id;
				$item->title = $row->title;
				$item->alias = $row->alias;
				$item->description = $row->description;
				$item->hits = $row->hits;
				$item->state = $row->state;
              //$item->website = $fieldsModel->raw;
				$item->created_date = $this->dateCreate($row->created);
                				
				//get category name
				$category 	= FD::table('GroupCategory');
				$category->load($row->category_id);
				$item->category_id = $row->category_id;
				$item->category_name = $category->get('title');
				$item->cover = $grpobj->getCover();

				$item->created_by = $row->creator_uid;
				$item->creator_name = JFactory::getUser($row->creator_uid)->username;
				//$item->type = ($row->type == 1 )?'Public':'Public';
				$item->type = $row->type;
				$item->params = (!empty($row->params))?$row->params:false;
				
				$item->more_info = $field_arr;
				
				foreach($row->avatars As $ky=>$avt)
				{
					$avt_key = 'avatar_'.$ky;
					$item->$avt_key = JURI::root().'media/com_easysocial/avatars/group/'.$row->id.'/'.$avt;

					$fst = JFile::exists('media/com_easysocial/avatars/group/'.$row->id.'/'.$avt);
					//set default image
					if(!$fst)
					{
						$item->$avt_key = JURI::root().'media/com_easysocial/avatars/group/'.$ky.'.png';
					}
				}
				
				//$obj->members = $row->members;
				$grp_obj = FD::model('Groups');
				$item->member_count = $grp_obj->getTotalMembers($row->id);
				//$obj->cover = $grp_obj->getMeta($row->id);
				
				$alb_model = FD::model('Albums');
				
				$uid = $row->id.':'.$row->title;

				$filters = array('uid'=>$uid,'type'=>'group');
				//get total album count
				$item->album_count = $alb_model->getTotalAlbums($filters);

				//get group album list
				//$albums = $alb_model->getAlbums($uid,'group');
				
				$item->isowner = ( $row->creator_uid == $userid )?true:false;
				$item->ismember = in_array( $userid,$row->members );
				$item->approval_pending = in_array( $userid,$row->pending );

				$result[] = $item;
			}
		}
		return $result;
		
	}
	
	//function for create profile schema
	public function profileSchema($other_user_id,$userid) 
	{

		$log_user_obj = FD::user($userid);
		$other_user_obj = FD::user($other_user_id);
		
		$user_obj = $this->createUserObj($other_user_id);
		$user_obj->isself = ($userid == $other_user_id )?true:false;
		$user_obj->cover = $other_user_obj->getCover();

		$user_obj->isblocked_me = $log_user_obj->isBlockedBy($other_user_id);
		$user_obj->isblockedby_me = $other_user_obj->isBlockedBy($userid);

		if( $userid != $other_user_id )
		{
			$frnd_mod = FD::model( 'Friends' );
			$trg_obj = FD::user( $other_user_id );
			$user_obj->isfriend = $trg_obj->isFriends( $userid );

			$user_obj->isfollower = $trg_obj->isFollowed( $userid );
			$user_obj->approval_pending = $frnd_mod->isPendingFriends($userid,$other_user_id);

			//$user_obj->approval_pending = $user->isPending($other_user_id);
		}
		
		//$user_obj->friend_count = $other_user_obj->getTotalFriends();
		//$user_obj->follower_count = $other_user_obj->getTotalFollowers();
		//$user_obj->badges = $this->createBadge($other_user_obj->getBadges());
		$user_obj->points = $other_user_obj->getPoints();
		
		
		return $user_obj;
	}

	//create badge object list
	public function createBadge($rows)
	{
		$badges = array();

		foreach( $rows as $row )
		{
			$std_obj = new stdClass();
			$std_obj->id = $row->id;
			$std_obj->title = JText::_($row->title);
			$std_obj->description = JText::_($row->description);
			$std_obj->alias = $row->alias;
			$std_obj->howto = JText::_($row->howto);
			$std_obj->avatar = JURI::root().$row->avatar;
			$std_obj->achieved_date = $row->achieved_date;
			$std_obj->created = $row->created;
	
			$badges[] = $std_obj; 
		}
		return $badges;
	}
	
	//function for create user schema
	public function userSchema($rows) 
	{
		$data = array();
		if(empty($rows))
			return $data;

		
		foreach($rows as $row)
		{
			$data[] = $this->createUserObj($row->id);
		}
		
		return $data;
	}
	
	//function for create message schema
	public function conversionSchema($rows,$log_user) 
	{
		$conv_model = FD::model('Conversations');
		$result = array();
		
		foreach($rows as $ky=>$row)
		{
			if(isset($row->id))
			{
				$item = new converastionSimpleSchema();
				$participant_usrs = $conv_model->getParticipants( $row->id );
				$con_usrs = array();

				foreach($participant_usrs as $ky=>$usrs)
				{
					if($usrs->id && ($log_user != $usrs->id) )
					$con_usrs[] =  $this->createUserObj($usrs->id);
				}
					
				$item->conversion_id = $row->id;
				$item->created_date = $row->created;
				$item->lastreplied_date = $row->lastreplied;
				$item->isread = $row->isread;
				$item->messages = $row->message;
				$item->lapsed = $this->calLaps($row->lastreplied);
				$item->participant = $con_usrs;

				$result[] = $item;
			}
		}

		return $result;
	}
	
	//function for create message schema
	public function messageSchema($rows) 
	{
		$conv_model = FD::model('Conversations');
		$result = array();
		
		foreach($rows as $ky=>$row)
		{
			if(isset($row->id))
			{
				$item = new MessageSimpleSchema();

				$item->id = $row->id;
				$item->message = $row->message;
				$item->attachment = null;
				//$item->attachment = $conv_model->getAttachments($row->id);
				$item->created_by = $this->createUserObj($row->created_by);
				$item->created_date = $this->dateCreate($row->created);
				$item->lapsed = $this->calLaps($row->created);
				$item->isself = ($this->log_user == $row->created_by)?1:0;
							
				$result[] = $item;
			}
		}

		return $result;
	}
	
	//function for create message schema
	public function replySchema($rows) 
	{
		//$conv_model = FD::model('Conversations');
		$result = array();
		
		foreach($rows as $ky=>$row)
		{
			if(isset($row->id))
			{
				$item = new ReplySimpleSchema();

				$item->id = $row->id;
				
				//format content
				$row->content = str_replace('[','<',$row->content);
				$row->content = str_replace(']','>',$row->content);				
				
				$item->reply = $row->content;
				$item->created_by = $this->createUserObj($row->created_by);
				$item->created_date = $this->dateCreate($row->created);
				$item->lapsed = $this->calLaps($row->created);
				$result[] = $item;
			}
		}

		return $result;
	}
	
	//calculate laps time
	function calLaps($date)
	{

		if( (strtotime($date) == 0) || ($date == NULL) || $date == '0000-00-00 00:00:00' )
		{
			return 0;
		}
	
		return $lap_date = FD::date($date)->toLapsed();
		
	}
	
	//create user object
	public function createUserObj($id){

		if($id)
		{
		
		$user = FD::user($id);
		
		/*
		$actor = new stdClass;
		
		$image = new stdClass;
		
		$actor->id = $id;
		$actor->username = $user->username;
		
		$image->image_small = $user->getAvatar('small');
		$image->image_medium = $user->getAvatar();
		$image->image_large = $user->getAvatar('large');
		
		$image->cover_image = $user->getCover();
		
		$actor->image = $image;
		
		return $actor;
		*/
		$es_params = FD::config();
		$actor = new userSimpleSchema();
		$image = new stdClass;
		
		$actor->id = $id;
		$actor->username = $user->username;
		$actor->name = $user->name;

		//ES config dependent username
		if($es_params->get('users')->displayName == 'username')
		{
			$actor->display_name = $user->username;
		}
		else
		{
			$actor->display_name = $user->name;
		}

		$image->image_small = $user->getAvatar('small');
		$image->image_medium = $user->getAvatar();
		$image->image_large = $user->getAvatar('large');
		$image->image_square = $user->getAvatar('square');
		
		//set default image
		/*if(!file_exists($image->image_small))
		{
			$image->image_small = JURI::root().'media/com_easysocial/avatars/user/small.png';
			$image->image_medium = JURI::root().'media/com_easysocial/avatars/user/medium.png';
			$image->image_large = JURI::root().'media/com_easysocial/avatars/user/large.png';
		}*/
		
		$image->cover_image = $user->getCover();
		
		$actor->image = $image;

		$actor->points = $user->points;
		$actor->totale_badges = $user->getTotalBadges();  		
		$actor->badges = $this->createBadge($user->getBadges());
		
		$fmodel	= FD::model( 'Friends' );
		//$actor->friend_count = $fmodel->getFriendsCount( $id );
		$actor->friend_count = $user->getTotalFriends();


		$actor->follower_count = $user->getTotalFollowers();
	
		return $actor;
		}
				
		}

	//date formatting for ios 9 
	public function listDate($date)
	{
		$datetime = new DateTime($date);
		/*list($year,$month,$day,$hour,$min,$sec,$msec) = explode('-',$datetime->format('Y-m-d-H-i-s-u'));
		return $ios9_str_dt = $year.','.$month.','.$day.','.$hour.','.$min.','.$sec.','.$msec;
		*/
		//alternate code for ios9 sending array
		$i_dt = array();
		$i_dt['year'] = $datetime->format('Y');
		$i_dt['month'] = $datetime->format('m');
		$i_dt['day'] = $datetime->format('d');
		$i_dt['hour'] = $datetime->format('H');
		$i_dt['minutes'] = $datetime->format('i');
		$i_dt['seconds'] = $datetime->format('s');
		$i_dt['microsec'] = $datetime->format('u');
                return $i_dt;
	}
	
	//format date for event
	public function dateCreate($dt) {

		$date=date_create($dt);
		return $newdta = date_format($date,"l,F j Y");
	}
	
	public function sanitize($text) {
		$text = htmlspecialchars_decode($text);
		$text = str_ireplace('&nbsp;', ' ', $text);
		
		return $text;
	}
	//create user frnd details nod
	public function frnd_nodes($data,$user)
	{
		//$user = JFactory::getUser($this->plugin->get('user')->id);
		$frnd_mod = FD::model( 'Friends' );
		$model = FD::model('Blocks');
		$list = array();
		foreach($data as $k=>$node)
		{
			$res = (bool) $model->isBlocked($user->id, $node->id);

			if($node->id != $user->id)
			{								
				$node->mutual = $frnd_mod->getMutualFriendCount($user->id,$node->id);
				$node->isFriend = $frnd_mod->isFriends($user->id,$node->id);
				$node->approval_pending = $frnd_mod->isPendingFriends($user->id,$node->id);			
				$node->isBlock = $res;			
			}
		}		
		return $data;	
	}	
    
    //function for getting polls 	
	 public function pollsSchema($rows)
	{
		 $item=array();
		foreach($rows as $row)
		{		
				$item = new pollsSchema();
				
				$item->id = $row->id;
				$item->element = $row->element;
				$item->uid  = $row->uid;
				$item->title  = $row->title;
				$item->multiple  = $row->multiple;
				$item->locked  = $row->locked;
				$item->cluster_id  = $row->cluster_id;
				$item->created_by  = $row->created_by;
				$item->created =$row->created;
				$item->expiry_date  = $row->expiry_date;
		
				$result[] = $item;		
		}
		return $result;
	}		
    
    //function for getting all videos 	
	public function videosSchema($rows,$userid)	
	{	
		foreach($rows as $ky=>$row)
		{
				$item = new VideoSimpleSchema();

                $model = FD::model( 'Videos' );
				
				$category 	= FD::table('VideoCategory');				
                $category->load($row->category_id);				
				
                //$video = FD::video($row->id);
                $video = ES::video();
                $video->load($row->id);			

                $user = JFactory::getUser();
                $isRoot = $user->authorise('core.admin');
																	
				$item->id = $row->id;
				$item->title = $row->title;
				$item->description = $row->description;
				$item->created_by = $this->createUserObj($row->user_id);
				$item->uid = $row->uid;
				$item->type = $row->type;
				$item->created = $video->getCreatedDate();
				$item->state = $row->state;
				$item->featured = $row->featured;
				$item->category_id = $row->category_id;
				$item->category_name = $category->get('title');
				$item->hits = $video->getHits();    //$row->hits;
				$item->duration = $video->getDuration();    //$row->duration;
				$item->size = $row->size;
				$item->params = json_decode($row->params,true);
				$item->storage = $row->storage;
				$item->path = $row->path;
				$item->original = $row->original;
				$item->file_title = $row->file_title;
				$item->source = $row->source;	
				$item->thumbnail = $row->thumbnail;
				$item->likes = $video->getLikesCount();
                $item->comments = $video->getCommentsCount();
                $item->isAdmin = $isRoot;	
                $item->stream_id = $model->getStreamId($row->id,'create');    
	
				$result[] = $item;				
		}
		return $result;	
	}
	
	//function for mapping video object
	public function videoMap($rows)
	{
		foreach($rows as $row)
		{
				$item = new VideoSimpleSchema();
					
				$item->id = $row->id;
				$item->title = $row->title;
				$item->description = $row->description;
				$item->user_id = $row->user_id;
				$item->uid = $row->uid;
				$item->type = $row->type;
				$item->created = $row->created;
				$item->state = $row->state;
				$item->featured = $row->featured;
				$item->category_id = $row->category_id;
				$item->hits = $row->hits;
				$item->duration = $row->duration;
				$item->size = $row->size;
				$item->params = $row->params;
				$item->storage = $row->storage;
				$item->path = $row->path;
				$item->original = $row->original;
				$item->file_title = $row->file_title;
				$item->source = $row->source;	
				$item->thumbnail = $row->thumbnail;
				$item->message = "Video uploaded successfully";	
		
				$result[] = $item;	
				return $result;
		}
	}
	
}

