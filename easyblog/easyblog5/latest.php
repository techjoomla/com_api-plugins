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

/**
 * API class EasyblogApiResourceLatest
 *
 * @since  1.0
 */
class EasyblogApiResourceLatest extends ApiResource
{
	/**
	 * Method to get the list of available logs on site
	 *
	 * @return  ApiPlugin response object
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$input = JFactory::getApplication()->input;
		$search = $input->get('search', '', 'STRING');
		$featuredRequested = $input->get('featured', 0, 'INT');
		$tags = $input->get('tags', 0, 'INT');
		$userId = $input->get('user_id', 0, 'INT');
		$limitstart = $input->get('limitstart', 0, 'INT');
		$limit = $input->get('limit', 10, 'INT');

		/*
		 * @FIXME add proper paginagioon support once the stackideas support it
		 * for now it won't support limit if the limitstart is 0 and will take the limit from easyblog config
		 */
		$pagination = $limitstart . ' , ' . $limit;
		$posts = array();
		$model = EB::model('Blog');
		$blogs = array();
		$latestData = array();

		$featured = $model->getFeaturedBlog('', EBLOG_MAX_FEATURED_POST);
		$excludeIds = array();

		if ($featuredRequested)
		{
			$blogs = $featured;
		}
		elseif ($tags)
		{
			// @TODO add limit support
			$blogs = $model->getTaggedBlogs($tags);
		}
		elseif ($userId)
		{
			// @TODO Add ACL support
			$blogs = $model->getBlogsBy('blogger', $userId, 'latest', $pagination, EBLOG_FILTER_PUBLISHED, $search, true, null, false, false, true, '', '',
					null, 'listlength', false, '', '', false, '', '');
		}
		else
		{
			foreach ($featured as $item)
			{
				$excludeIds[] = $item->id;
			}

			$blogs = $model->getBlogsBy('', '', 'latest', $pagination, EBLOG_FILTER_PUBLISHED, $search, true, $excludeIds, false, false, true, '', '',
					null, 'listlength', false, '', '', false, '', '');
		}

		$blogs = EB::formatter('list', $blogs, false);
		$schemaObject = new EasyBlogSimpleSchema_Plg;
		$model = EB::model('Ratings');

		foreach ($blogs as $blog)
		{
			$item = $schemaObject->mapPost($blog, '', 100);
			$ratingValue = $model->getRatingValues($item->postid, 'entry');
			$item->rate = $ratingValue;

			if ($item->rate->ratings == 0)
			{
				$item->rate->ratings = - 2;
			}

			$posts[] = $item;
		}

		$apiResponse = new stdClass;
		$apiResponse->result = $posts;
		$this->plugin->setResponse($apiResponse);
	}

	/**
	 * Method 
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public static function getName()
	{
	}

	/**
	 * Method 
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public static function describe()
	{
	}
}
