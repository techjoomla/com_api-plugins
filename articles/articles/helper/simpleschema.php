<?php

/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die('Restricted access');
//jimport( 'simpleschema.easyblog.blog.post' );
require_once JPATH_SITE.'/plugins/api/articles/articles/blogs/blog/post.php';
require_once JPATH_SITE.'/plugins/api/articles/articles/blogs/category.php';
require_once JPATH_SITE.'/plugins/api/articles/articles/helper/contenthelper.php';

class BlogappSimpleSchema 
{
		public function mapPost($blog, $strip_tags='', $text_length=0, $skip=array())
	{
//print_r($blog);die("in schema");
		$creator = JFactory::getUser( $blog->created_by );
		
		$item = new PostSimpleSchema;

		$item->textplain = $blog->fulltext;
		
		$item->postid = $blog->id;
		$item->title = $blog->title;
		$item->introtext = $this->sanitize($blog->introtext);

		$item->text = $this->sanitize($blog->fulltext);
		
		if(empty($item->text))
		{
			$item->text = $blog->introtext." ".$blog->fulltext; 
			$item->textplain = $blog->introtext." ".$blog->fulltext; 
		}
		
		$item->textplain = $item->textplain;

		$item->image = new stdClass();
		
		$img_obj = json_decode($blog->images);
		if(isset($img_obj->image_intro))
		{
			$item->image->intro_url = (string)JURI::root().$img_obj->image_intro;
			$item->image->full_image_url = JURI::root().$img_obj->image_fulltext;
		}
		else
		{
			$item->image->intro_url = null;
			$item->image->full_image_url = null;
		}
		$item->created_date = JHTML::_('date', $blog->created, JText::_('DATE_FORMAT_LC2'));

		$item->author->name = $creator->username;
		$item->author->photo = null;
		
		$item->category->categoryid = $blog->catid;
		$item->category->title = $blog->category_title;
		//condition for changing path - not support in app
		if (strpos($item->text,'href="http') == false)
		{
			$item->introtext = str_replace('href="images','href="'.JURI::root().'images',$item->introtext);
		    $item->text = str_replace('href="images','href="'.JURI::root().'images',$item->text);
		}

		if (strpos($item->text,'src="http') == false)
		{    
		    $item->introtext = str_replace('src="','src="'.JURI::root(),$item->introtext);
			$item->text = str_replace('src="','src="'.JURI::root(),$item->text);	
		}
		$item->url = JURI::root() . trim('index.php?option=com_content&view=article&id=' . $item->postid .':'.$blog->alias );
		
		//load content module and position
		$c_obj = new BlogappContentHelper();
		$c_obj->loadContent($item);
		
		//$item->text = str_replace('href="images','href="'.JURI::root().'images',$item->text);
		//$item->text = str_replace('src="','src="'.JURI::root(),$item->text);

		return $item;
	}
	
	public function mapCategory( $cat )
	{
		$item = new CategorySimpleSchema();
		
		$item->categoryid = $cat->id;
		$item->title = $cat->title;;
		//$item->description = $this->sanitize($cat->introtext);
		//$item->created_date = $cat->created;
		$item->path = $cat->path;
		$item->level = $cat->level;
		$item->lft = $cat->lft;
		$item->rgt = $cat->rgt;
		
		return $item;
	}
	
	public function sanitize($text) {
		$text = htmlspecialchars_decode($text);
		$text = str_ireplace('&nbsp;', ' ', $text);
		
		return $text;
	}
		
}


