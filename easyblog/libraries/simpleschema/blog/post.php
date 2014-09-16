<?php
jimport('simpleschema.person');
jimport('simpleschema.category');

class PostSimpleSchema {

	public $postid;
	
	public $title;
	
	public $text;
	
	public $textplain;
	
	public $image = array();
	
	public $created_date;
	
	public $created_date_elapsed;
	
	public $updated_date;
	
	public $author;
	
	public $comments;
	
	public $url;
	
	public $tags = array();
	
	public $rating;
	
	public $category;
	
	public function __construct() {
		$this->author 		= new PersonSimpleSchema;
		$this->category 	= new CategorySimpleSchema;
	}

}
