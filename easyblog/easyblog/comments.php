<?php
defined('_JEXEC') or die( 'Restricted access' );
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

jimport('joomla.user.user');
jimport( 'simpleschema.person' );
jimport( 'simpleschema.blog.post' );
jimport( 'simpleschema.blog.comment' );

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

class EasyblogApiResourceComments extends ApiResource
{

	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$id = $input->get('id', null, 'INT');
		$comments = array();

		// If we have an id try to fetch the blog
		$blog = EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $id );
		
		if (!$blog->id) {
			$this->plugin->setResponse( $this->getErrorResponse(404, 'Invalid Blog') );
			return;
		}

		$rows = $model->getBlogComment($id);
		
		foreach ($rows as $row) {
			$item = new CommentSimpleSchema;
			$item->commentid = $row->id;
			$item->postid = $row->post_id;
			$item->title = $row->title;
			$item->text = EasyBlogCommentHelper::parseBBCode($row->comment);
			$item->textplain = strip_tags(EasyBlogCommentHelper::parseBBCode($row->comment));
			$item->created_date = $row->created;
			$item->created_date_elapsed = EasyBlogDateHelper::getLapsedTime( $row->created );
			$item->updated_date = $row->modified;
			
			// Author
			$item->author->name = isset($row->poster->nickname) ? $row->poster->nickname : $row->name;
			$item->author->photo = isset($row->poster->avatar) ? $row->poster->avatar : 'default_blogger.png';
			$item->author->photo = JURI::root() . 'components/com_easyblog/assets/images/' . $item->author->photo;
			$item->author->email = $row->email;
			$item->author->website = isset($row->poster->url) ? $row->poster->url : $row->url;
			
			$comments[] = $item;
		}
		
		$this->plugin->setResponse( $comments );
		
	}
	
	public static function getName() {
		
	}
	
	public static function describe() {
		
	}
	
}
