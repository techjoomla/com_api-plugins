<?php
defined('_JEXEC') or die( 'Restricted access' );
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

jimport('joomla.user.user');
jimport( 'simpleschema.category' );
jimport( 'simpleschema.person' );
jimport( 'simpleschema.blog.post' );
jimport( 'simpleschema.blog.comment' );

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

class EasyblogApiResourceComments extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );

	}
	
	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$id = $input->get('id', null, 'INT');
		$comments = array();

		$rows = $model->getBlogComment($id);
		
		foreach ($rows as $row) {
			$item = new CommentSchema;
			$item->commentid = $row->;
			$item->postid = $id;
			
			$comments[] = $item;
		}
		
		$this->plugin->setResponse( $comments );
		
	}
	
	public static function getName() {
		
	}
	
	public static function describe() {
		
	}
	
}
