<?php

class CommentSimpleSchema {

	public $commentid;
	
	public $postid;
	
	public $title;
	
	public $text;
	
	public $textplain;
	
	public $created_date;
	
	public $updated_date;
	
	public $created_date_elapsed;
	
	public $author;
	
	public function __construct() {
		$this->author 		= new PersonSimpleSchema;
	}

}
