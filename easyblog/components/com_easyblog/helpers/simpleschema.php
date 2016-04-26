<?php
/**
 * @package		EasyBlog
 * @copyright	Copyright (C) 2011 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 *
 * EasyBlog is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

defined('_JEXEC') or die('Restricted access');
jimport( 'simpleschema.blog.post' );

class EasyBlogSimpleSchemaHelper11
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
		
		if( $config->get( 'main_rss_content' ) == 'introtext' )
		{
			$blog->text = ( !empty( $row->intro ) ) ? $row->intro : $row->content;
		}
		else
		{
			$blog->text	= $row->intro . $row->content;
		   
		}
		$blog->text = EasyBlogHelper::getHelper( 'Videos' )->strip( $blog->text );
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

		$item->url = JURI::root() . trim('index.php?option=com_easyblog&view=entry&id=' . $blog->id, '/');

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
