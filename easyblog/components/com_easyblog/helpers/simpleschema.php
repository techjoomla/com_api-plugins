<?php

defined('_JEXEC') or die('Restricted access');
jimport( 'simpleschema.blog.post' );

class EasyBlogSimpleSchemaHelper
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
		
		$item->url = JURI::root() . trim(EasyBlogRouter::_('index.php?option=com_easyblog&view=entry&id=' . $blog->id ), '/');
		
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
