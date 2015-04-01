<?php

defined('_JEXEC') or die('Restricted access');
jimport( 'libraries.schema.group' );

require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/groups.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/covers.php';
require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/models/albums.php';

require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/group.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/message.php';
require_once JPATH_SITE.'/plugins/api/easysocial/libraries/schema/discussion.php';

class EasySocialApiMappingHelper
{
	public $log_user = 0;
	
	public function mapItem($rows, $obj_type='', $userid = 0 , $strip_tags='', $text_length=0, $skip=array()) {
	
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
						return $this->profileSchema($rows);
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
			case 'reply':
						return $this->replySchema($rows);
						break;
			case 'discussion':
						return $this->discussionSchema($rows);
						break;
		}
		
		return $item;
	}
	
	//function for discussion main obj
	public function discussionSchema($rows) 
	{
		//$conv_model = FD::model('Conversations');
		$result = array();

		foreach($rows as $ky=>$row)
		{

			$item = new discussionSimpleSchema();

			$item->id = $row->id;
			$item->title = $row->title;
			$item->description = null;
			//$item->attachment = $conv_model->getAttachments($row->id);
			$item->created_by = $this->createUserObj($row->created_by);
			$item->created_date = $this->dateCreate($row->created);
			$item->lapsed = $this->calLaps($row->created);
			$item->hits = $row->hits;
			$item->replies_count = $row->total_replies;
			$item->last_replied = $this->calLaps($row->last_replied);
			//$item->replies = 0;
			$last_repl = array(0=>$row->lastreply);
			
			$item->replies = $this->discussionReply($last_repl);
			
			$result[] = $item;
		}

		return $result;
	}
	
	//function for discussion reply obj
	public function discussionReply($rows) 
	{
		//$conv_model = FD::model('Conversations');
		$result = array();
		
		foreach($rows as $ky=>$row)
		{
			$item = new discussionReplySimpleSchema();

			$item->id = $row->id;
			$item->reply = $row->content;
			$item->created_by = $this->createUserObj($row->created_by);
			$item->created_date = $this->dateCreate($row->created);
			$item->lapsed = $this->calLaps($row->created);
			
			$result[] = $item;
		}

		return $result;
	}
	
	//function for create category schema
	public function categorySchema($rows) 
	{
		return false;
	}
	
	//function for create group schema
	public function groupSchema($rows=null,$userid=0) 
	{
		if($rows == null || $userid == 0)
		{
			return false;
		}

		$result = array();		
	
		$user = JFactory::getUser($userid);

		foreach($rows as $ky=>$row)
		{
			$item = new GroupSimpleSchema();
			
			$item->id = $row->id;
			$item->title = $row->title;
			$item->alias = $row->alias;
			$item->description = $row->description;
			$item->hits = $row->hits;
			$item->state = $row->state;
			$item->created_date = $this->dateCreate($row->created);
			
			//get category name
			$category 	= FD::table('GroupCategory');
			$category->load($row->category_id);
			$item->category_id = $row->category_id;
			$item->category_name = $category->get('title');
			//$item->cover = $row->cover->('title');

			$item->created_by = $row->creator_uid;
			$item->creator_name = JFactory::getUser($row->creator_uid)->username;
			$item->type = ($row->type == 1 )?'Private':'Public';
		
			foreach($row->avatars As $ky=>$avt)
			{
				$avt_key = 'avatar_'.$ky;
				$item->$avt_key = JURI::root().'media/com_easysocial/avatars/group/'.$row->id.'/'.$avt;
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
		return $result;
		
	}
	
	//function for create profile schema
	public function profileSchema($rows) 
	{
		return false;
	}
	
	//function for create user schema
	public function userSchema($rows) 
	{
		return false;
	}
	
	//function for create comment schema
	public function commentSchema($rows) 
	{
		return false;
	}
	
	//function for create message schema
	public function messageSchema($rows) 
	{
		$conv_model = FD::model('Conversations');
		$result = array();
		
		foreach($rows as $ky=>$row)
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

		return $result;
	}
	
	//function for create message schema
	public function replySchema($rows) 
	{
		//$conv_model = FD::model('Conversations');
		$result = array();
		
		foreach($rows as $ky=>$row)
		{

			$item = new ReplySimpleSchema();

			$item->id = $row->id;
			$item->reply = $row->content;
			$item->created_by = $this->createUserObj($row->created_by);
			$item->created_date = $this->dateCreate($row->created);
			$item->lapsed = $this->calLaps($row->created);
			$result[] = $item;
		}

		return $result;
	}
	
	//calculate laps time
	function calLaps($date)
	{
		$start_date = new DateTime($date);
		$since_start = $start_date->diff(new DateTime(date('Y-m-d h:i:s a')));
		$time_str = array();
		foreach($since_start as $ky=>$val)
		{
			
			switch($ky)
			{
				case 'y': $time_str[] = ($val)?$val.' Years':null;
							break;
				case 'm': $time_str[] = ($val)?$val.' month':null;
							break;
				case 'd': $time_str[] = ($val)?$val.' day':null;
							break;
				case 'i': $time_str[] = ($val)?$val.' minute':null;
							break;
				case 's': $time_str[] = ($val)?$val.' seconds':null;
							break;
			}
			
		}
		
		return $str_time = implode(" ",array_filter($time_str));
	}
	
	//create user object
	public function createUserObj($id){
		
		$user = FD::user($id);
		
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
		
		}
	
	public function dateCreate($dt) {

			$date=date_create($dt);
			return $newdta = date_format($date,"l,F j Y");
	}
	
	public function sanitize($text) {
		$text = htmlspecialchars_decode($text);
		$text = str_ireplace('&nbsp;', ' ', $text);
		
		return $text;
	}
		
}
