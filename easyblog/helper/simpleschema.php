<?php

/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die('Restricted access');
jimport( 'simpleschema.easyblog.blog.post' );

class EasyBlogSimpleSchema_plg 
{
	public function mapPost($row, $strip_tags='', $text_length=0, $skip=array()) {
		$config	= EasyBlogHelper::getConfig();

		$blog = EB::table( 'Blog' );
		$blog->load( $row->id );

		$profile = EB::table( 'Profile', 'Table' );
		$profile->load( $row->created_by );

		$created = EasyBlogDate::dateWithOffSet( $row->created );
		$formatDate = true;
	
		if(EasyBlogHelper::getJoomlaVersion() >= '1.6')
		{
			$eb_lang = new EasyBlogString();
			$langCode = $eb_lang->getLangCode();
			if($langCode != 'en-GB' || $langCode != 'en-US')
				$formatDate = false;
		}
		$blog->created = $created->toMySQL();
		$blog->text	= $row->intro . $row->content;
		   
		$config->set('max_video_width', 320);
		$config->set('max_video_width', 180);
		$blog->text = EasyBlogHelper::helper( 'Videos' )->processVideos( $blog->text );
		
		$adsnc = new EasyBlogAdsense();
		$blog->text = $adsnc->stripAdsenseCode( $blog->text );
		
		$category = EB::table( 'Category', 'Table' );
		$category->load( $row->category_id );
		
		$item = new PostSimpleSchema;
		$item->textplain = $blog->text;
	
		// @TODO : Take care of a case when strip tags and length are used together
		if ($strip_tags) {
			$item->textplain = strip_tags($blog->text, $strip_tags);
		}
		
		if ($text_length > 0) {
			$pos = JString::strpos(strip_tags($item->textplain), ' ', false);
			$item->textplain = JString::substr(strip_tags($blog->text), 0, $pos);
		}	
		//$image_data = json_decode($blog->image);

		$item->postid = $blog->id;
		$item->title = $blog->title;		
		$item->text = $blog->text;
		$item->textplain = $this->sanitize($item->textplain);

		$item->image = new stdClass();
		if($row->image)
		{
			//$item->image->url = $blog->getImage();
			$item->image->url = $row->getImage('large');
			$item->image->url = 'http:'.$item->image->url;
			//$item->image->url = ltrim($item->image->url,'//');
		}
		else
		{
			$item->image->url = null;
		}
		//$item->image->url = ($image_data->url)?$image_data->url:'';
		//$item->image->url = null;
		
		$item->created_date = $blog->created;
		$ebdate = new EasyBlogDate();
		$item->created_date_elapsed	= $ebdate->getLapsedTime( $blog->created );
		
		$item->author->name = $profile->nickname;
		$item->author->photo = JURI::root() . $profile->avatar;
		
		$item->category->categoryid = $category->id;
		$item->category->title = $category->title;
		
		$item->url = JURI::root() . trim(EBR::_('index.php?option=com_easyblog&view=entry&id=' . $blog->id ), '/');
		
		// Tags
		$modelPT	= EasyBlogHelper::getModel( 'PostTag' );
		$item->tags		= $modelPT->getBlogTags($blog->id);

		foreach ($skip as $v) {
			unset($item->$v);
		}
		//handle image path	
		if(strpos($item->text,'src="data:image') == false)
		{	
			
			if (strpos($item->text,'href="index'))
			{
				$item->introtext = str_replace('href="index','href="'.JURI::root().'index',$item->introtext);
			    $item->text = str_replace('href="index','href="'.JURI::root().'index',$item->text);
				//$item->text = str_replace('src="','src="'.JURI::root(),$item->text);	
			}
			
			if (strpos($item->text,'href="images'))
			{
				$item->introtext = str_replace('href="images','href="'.JURI::root().'images',$item->introtext);
			    $item->text = str_replace('href="images','href="'.JURI::root().'images',$item->text);
				//$item->text = str_replace('src="','src="'.JURI::root(),$item->text);	
			}

			if ( strpos($item->text,'src="images') || strpos($item->introtext,'src="images') )
			{		    
			    $item->introtext = str_replace('src="','src="'.JURI::root(),$item->introtext);
				$item->text = str_replace('src="','src="'.JURI::root(),$item->text);	
			}

			if ( strpos($item->text,'src="/'))
			{		    
			    $item->introtext = str_replace('src="/','src="'.'http://',$item->introtext);
				$item->text = str_replace('src="/','src="'.'http://',$item->text);	
			}
			
			if ( strpos($item->text,'href="/'))
			{		    
			    $item->introtext = str_replace('href="/','href="'.'http://',$item->introtext);
				$item->text = str_replace('href="/','href="'.'http://',$item->text);	
			}
		}

		return $item;
	}
	
	public function sanitize($text) {
		$text = htmlspecialchars_decode($text);
		$text = str_ireplace('&nbsp;', ' ', $text);
		
		return $text;
	}
		
}

class EasyBlogSimpleSchema_4 
{
		public function mapPost($row, $strip_tags='', $text_length=0, $skip=array()) {
		$config	= EasyBlogHelper::getConfig();
		
		$blog = EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $row->id );

		$profile = EasyBlogHelper::getTable( 'Profile', 'Table' );
		$profile->load( $row->created_by );

		$created = EasyBlogDateHelper::dateWithOffSet( $row->created );
		$formatDate = true;
		if(EasyBlogHelper::getJoomlaVersion() >= '1.6')
		{
			$langCode = EasyBlogStringHelper::getLangCode();
			if($langCode != 'en-GB' || $langCode != 'en-US')
				$formatDate = false;
		}
		$blog->created = $created->toMySQL();
		$blog->text	= $row->intro . $row->content;
		   
		$config->set('max_video_width', 320);
		$config->set('max_video_width', 180);
		$blog->text = EasyBlogHelper::getHelper( 'Videos' )->processVideos( $blog->text );
		$blog->text = EasyBlogGoogleAdsense::stripAdsenseCode( $blog->text );
		
		$category = EasyBlogHelper::getTable( 'Category', 'Table' );
		$category->load( $row->category_id );
		
		$item = new PostSimpleSchema;
		$item->textplain = $blog->text;
		
		// @TODO : Take care of a case when strip tags and length are used together
		if ($strip_tags) {
			$item->textplain = strip_tags($blog->text, $strip_tags);
		}
		
		if ($text_length > 0) {
			$pos = JString::strpos(strip_tags($item->textplain), ' ', $text_length);
			$item->textplain = JString::substr(strip_tags($blog->text), 0, $pos);
		}

		$image_data = json_decode($blog->image);
		
		$item->postid = $blog->id;
		$item->title = $blog->title;		
		$item->text = $blog->text;
		$item->textplain = $this->sanitize($item->textplain);
		
		$item->image = $blog->getImage();
		$item->image->url = $image_data->url;
		$item->created_date = $blog->created;
		$item->created_date_elapsed	= EasyBlogDateHelper::getLapsedTime( $blog->created );
		
		$item->author->name = $profile->nickname;
		$item->author->photo = JURI::root() . $profile->avatar;
		
		$item->category->categoryid = $category->id;
		$item->category->title = $category->title;
		
		$item->url = JURI::root() . trim(EBR::_('index.php?option=com_easyblog&view=entry&id=' . $blog->id ), '/');
		
		// Tags
		$modelPT	= EasyBlogHelper::getModel( 'PostTag' );
		$item->tags		= $modelPT->getBlogTags($blog->id);

		foreach ($skip as $v) {
			unset($item->$v);
		}

		return $item;
	}
	
	public function sanitize($text) {
		$text = htmlspecialchars_decode($text);
		$text = str_ireplace('&nbsp;', ' ', $text);
		
		return $text;
	}
}
