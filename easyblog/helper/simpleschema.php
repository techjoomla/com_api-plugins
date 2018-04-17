<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api-plugins
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\String\StringHelper;
jimport('simpleschema.easyblog.blog.post');

/**
 * API class EasyBlogSimpleSchema_Plg
 *
 * @since  5.0
 */
class EasyBlogSimpleSchema_Plg
{
	/**
	 * Map the post fields as per mobile app requirement
	 *
	 * @param   Object  $row         The EasyBlogPost class object
	 * @param   Array   $stripTags   Tags to strip
	 * @param   string  $textLength  The maximum text length allowed to show
	 * @param   Array   $skip        The array of tag which should be skip.
	 *
	 * @return  object
	 *
	 * @since   5.0
	 */
	public function mapPost($row, $stripTags = '', $textLength = 0, $skip = array())
	{
		$blogsMeta = new stdClass;
		$blogsMeta->text = $row->intro . $row->content;
		$blogsMeta->text = EasyBlogHelper::helper('Videos')->processVideos($blogsMeta->text);
		$row->contributionDisplay = JText::_('COM_EASYBLOG_BLOGS_WIDE');
		$contribution = $row->getBlogContribution();

		if ($contribution !== false)
		{
			$row->contributionDisplay = $contribution->getTitle();
		}

		$row->featured = $row->isFeatured;
		$adsense = new EasyBlogAdsense;
		$blogsMeta->text = $adsense->strip($blogsMeta->text);

		$item = new PostSimpleSchema;
		$item->textplain = $blogsMeta->text;

		// @TODO : Take care of a case when strip tags and length are used together
		if ($stripTags)
		{
			$item->textplain = strip_tags($item->textplain, $stripTags);
		}

		if ($textLength > 0)
		{
			$pos = StringHelper::strpos(strip_tags($item->textplain), ' ', false);
			$item->textplain = StringHelper::substr(strip_tags($item->textplain), 0, $pos);
		}

		$item->image = new stdClass;
		$item->postid = $row->id;
		$item->title = $row->title;
		$item->text = $blogsMeta->text;
		$item->textplain = $this->sanitize($item->textplain);
		$item->tags = $row->tags;
		$item->created_date = EB::date($row->created)->format();
		$item->created_date_elapsed = EB::date()->getLapsedTime($row->created);
		$item->author->name = $row->author->getName();
		$item->author->photo = $row->author->getAvatar();
		$item->author->email = $row->author->user->email;
		$item->category->categoryid = $row->category->id;
		$item->category->title = $row->category->title;
		$item->category->description = $row->category->description;
		$item->category->created_date = $row->category->created;
		$item->url = $row->getPermalink(true, true);
		$item->ispassword = $row->isPasswordProtected();
		$item->blogpassword = $row->blogpassword;
		$item->isowner = ($row->created_by == jfactory::getUser()->id);
		$item->isVoted = $row->hasVoted();
		$item->featured = boolval($row->isFeatured);
		$item->image->url = $row->getImage('large', true, true);

		// @TODO Optimize this code
		if (strpos($item->text, 'src="data:image') == false)
		{
			if (strpos($item->text, 'href="index'))
			{
				// $item->introtext = str_replace('href="index', 'href="' . JURI::root() . 'index', $item->introtext);
				$item->text = str_replace('href="index', 'href="' . JURI::root() . 'index', $item->text);
			}

			if (strpos($item->text, 'href="images'))
			{
				// $item->introtext = str_replace('href="images', 'href="' . JURI::root() . 'images', $item->introtext);
				$item->text = str_replace('href="images', 'href="' . JURI::root() . 'images', $item->text);
			}

			if (strpos($item->text, 'src="images')) // || strpos($item->introtext, 'src="images'))
			{
				// $item->introtext = str_replace('src="', 'src="' . JURI::root(), $item->introtext);
				$item->text = str_replace('src="', 'src="' . JURI::root(), $item->text);
			}

			if (strpos($item->text, 'src="/'))
			{
				// $item->introtext = str_replace('src="/', 'src="' . 'http://', $item->introtext);
				$item->text = str_replace('src="/', 'src="' . 'http://', $item->text);
			}

			if (strpos($item->text, 'href="/'))
			{
				// $item->introtext = str_replace('href="/', 'href="' . 'http://', $item->introtext);
				$item->text = str_replace('href="/', 'href="' . 'http://', $item->text);
			}
		}

		foreach ($skip as $v)
		{
			unset($item->$v);
		}

		return $item;
	}

	/**
	 * Method to sanitize string
	 *
	 * @param   string  $text  String to sanitize
	 * 
	 * @return  string
	 *
	 * @since 1.0
	 */
	public function sanitize($text)
	{
		$text = htmlspecialchars_decode($text);
		$text = str_ireplace('&nbsp;', ' ', $text);

		return $text;
	}
}

/**
 * API class EasyBlogSimpleSchema_4
 *
 * @since  4.0
 */
class EasyBlogSimpleSchema_4
{
	/**
	 * Map the post fields as per mobile app requirement
	 *
	 * @param   Object  $row          The EasyBlogPost class object
	 * @param   Array   $strip_tags   Tags to strip
	 * @param   string  $text_length  The maximum text length allowed to show
	 * @param   Array   $skip         The array of tag which should be skip.
	 *
	 * @return  object
	 *
	 * @since   4.0
	 */
	public function mapPost($row, $strip_tags = '', $text_length = 0, $skip = array())
	{
		$config = EasyBlogHelper::getConfig();

		$blog = EasyBlogHelper::getTable('Blog');
		$blog->load($row->id);

		$profile = EasyBlogHelper::getTable('Profile', 'Table');
		$profile->load($row->created_by);

		$created = EasyBlogDateHelper::dateWithOffSet($row->created);
		$formatDate = true;

		if (EasyBlogHelper::getJoomlaVersion() >= '1.6')
		{
			$langCode = EasyBlogStringHelper::getLangCode();

			if ($langCode != 'en-GB' || $langCode != 'en-US')
			{
				$formatDate = false;
			}
		}

		$blog->created = $created->toMySQL();
		$blog->text = $row->intro . $row->content;

		$config->set('max_video_width', 320);
		$config->set('max_video_width', 180);
		$blog->text = EasyBlogHelper::getHelper('Videos')->processVideos($blog->text);
		$blog->text = EasyBlogGoogleAdsense::stripAdsenseCode($blog->text);

		$category = EasyBlogHelper::getTable('Category', 'Table');
		$category->load($row->category_id);

		$item = new PostSimpleSchema;
		$item->textplain = $blog->text;

		// @TODO : Take care of a case when strip tags and length are used together
		if ($strip_tags)
		{
			$item->textplain = strip_tags($blog->text, $strip_tags);
		}

		if ($text_length > 0)
		{
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
		$item->created_date_elapsed = EasyBlogDateHelper::getLapsedTime($blog->created);

		$item->author->name = $profile->nickname;
		$item->author->photo = JURI::root() . $profile->avatar;
		$item->author->email = $profile->email;

		$item->category->categoryid = $category->id;
		$item->category->title = $category->title;

		$item->url = JURI::root() . trim(EasyBlogRouter::_('index.php?option=com_easyblog&view=entry&id=' . $blog->id), '/');

		// Tags
		$modelPT = EasyBlogHelper::getModel('PostTag');
		$item->tags = $modelPT->getBlogTags($blog->id);

		foreach ($skip as $v)
		{
			unset($item->$v);
		}

		return $item;
	}

	/**
	 * Method to sanitize string
	 *
	 * @param   string  $text  String to sanitize
	 * 
	 * @return  string
	 *
	 * @since 1.0
	 */
	public function sanitize($text)
	{
		$text = htmlspecialchars_decode($text);
		$text = str_ireplace('&nbsp;', ' ', $text);

		return $text;
	}
}
