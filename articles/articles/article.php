<?php
/**
 * @package    Com_Api
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die( 'Restricted access' );
require_once JPATH_SITE . '/components/com_content/models/articles.php';
require_once JPATH_SITE . '/components/com_content/models/article.php';

/**
 * Articles Resource
 *
 * @since  3.5
 */
class ContentApiResourceArticles extends ApiResource
{
	/**
	 * get Method to get all artcle data
	 *
	 * @return  json
	 *
	 * @since  3.5
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getArticles());
	}

	/**
	 * delete Method to delete artcle
	 *
	 * @return  json
	 *
	 * @since  3.5
	 */
	public function delete()
	{
		$this->plugin->setResponse('in delete');
	}

	/**
	 * getArticles Method to getArticles data
	 *
	 * @return  array
	 *
	 * @since  3.5
	 */
	public function getArticles()
	{
		$app = JFactory::getApplication();
		$items = array();
		$article_id = $app->input->get('id', 0, 'INT');
		$catid = $app->input->get('category_id', 0, 'INT');

		// Featured - hide,only,show
		$featured	= $app->input->get('featured', '', 'STRING');
		$auther_id	= $app->input->get('auther_id', 0, 'INT');
		$search = $app->input->get('search', '', 'STRING');
		$limitstart	= $app->input->get('limitstart', 0, 'INT');
		$limit	= $app->input->get('limit', 0, 'INT');

		$date_filtering	= $app->input->get('date_filtering', '', 'STRING');
		$start_date = $app->input->get('start_date_range', '', 'STRING');
		$end_date = $app->input->get('end_date_range', '', 'STRING');
		$realtive_date = $app->input->get('relative_date', '', 'STRING');

		$listOrder = $app->input->get('listOrder', 'ASC', 'STRING');

		$art_obj = new ContentModelArticles;

		$art_obj->setState('list.direction', $listOrder);

		if ($limit)
		{
			$art_obj->setState('list.start', $limitstart);
			$art_obj->setState('list.limit', $limit);
		}

		// Filter by category
		if ($catid)
		{
			$art_obj->setState('filter.category_id', $catid);
		}

		if ($search)
		{
			$art_obj->setState('list.filter', $search);
		}

		// Filter by auther
		if ($auther_id)
		{
			$art_obj->setState('filter.author_id', $auther_id);
		}

		// Filter by featured
		if ($featured)
		{
			$art_obj->setState('filter.featured', $featured);
		}

		// Filter by article
		if ($article_id)
		{
			$art_obj->setState('filter.article_id', $article_id);
		}

		// Filtering
		if ($date_filtering)
		{
			$art_obj->setState('filter.date_filtering', $date_filtering);

			if ($date_filtering == 'range')
			{
				$art_obj->setState('filter.start_date_range', $start_date);
				$art_obj->setState('filter.end_date_range', $end_date);
			}
		}

		$rows = $art_obj->getItems();

		$num_articles = $art_obj->getTotal();

		foreach ($rows as $subKey => $subArray)
		{
			if ($subArray->catid)
			{
				$subArray->catid = array('catid' => $subArray->catid,'title' => $subArray->category_title);
			}

			if ($subArray->created_by)
			{
				$subArray->created_by = array('id' => $subArray->created_by,'name' => $subArray->author,
				'avatar' => "http://172.132.45.45/joomla/investsure/images/samlpe.jpg");
			}

			if ($subArray->images)
			{
				$images = json_decode($subArray->images);

				foreach ($images as $key => $value)
				{
					if ($value)
					{
						$images->$key = JURI::base() . $value;
					}
				}

				$subArray->images = $images;
			}

			unset($subArray->checked_out);
			unset($subArray->checked_out_time);
			unset($subArray->created_by_alias);
			unset($subArray->modified_by);
			unset($subArray->modified_by_name);
			unset($subArray->urls);
			unset($subArray->attribs);
			unset($subArray->metadata);
			unset($subArray->metakey);
			unset($subArray->metadesc);
			unset($subArray->xreference);
			unset($subArray->readmore);
			unset($subArray->category_route);
			unset($subArray->category_access);
			unset($subArray->category_alias);
			unset($subArray->author_email);
			unset($subArray->parent_title);
			unset($subArray->parent_id);
			unset($subArray->parent_route);
			unset($subArray->parent_alias);
			unset($subArray->rating);
			unset($subArray->rating_count);
			unset($subArray->published);
			unset($subArray->parents_published);
			unset($subArray->alternative_readmore);
			unset($subArray->layout);
			unset($subArray->params);
			unset($subArray->displayDate);
			unset($subArray->author);
			unset($subArray->category_title);
		}

		$obj = new stdclass;
		$result = new stdClass;

		if (count($rows) > 0)
		{
			$result->results = $rows;
			$result->total = $num_articles;
			$obj->success = true;
			$obj->{'data'} = $result;

			return $obj;
		}
		else
		{
			$obj->success = false;
			$obj->message = 'System does not have articles';
		}

		return $obj;
	}

	/**
	 * Post is to create / upadte article
	 *
	 * @return  Bolean
	 *
	 * @since  3.5
	 */
	public function post()
	{
		$this->plugin->setResponse($this->CreateUpdateArticle());
	}

	/**
	 * CreateUpdateArticle is to create / upadte article
	 *
	 * @return  Bolean
	 *
	 * @since  3.5
	 */
	public function CreateUpdateArticle()
	{
		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			JTable::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
		}

		$obj = new stdclass;

		$app = JFactory::getApplication();
		$article_id = $app->input->get('id', 0, 'INT');

		if (empty($app->input->get('title', '', 'STRING')))
		{
			$obj->success = false;
			$obj->message = 'Title is Missing';

			return $obj;
		}

		if (empty($app->input->get('introtext', '', 'STRING')))
		{
			$obj->success = false;
			$obj->message = 'Introtext is Missing';

			return $obj;
		}

		if (empty($app->input->get('catid', '', 'INT')))
		{
			$obj->success = false;
			$obj->message = 'Category id is Missing';

			return $obj;
		}

		if ($article_id)
		{
			$article = JTable::getInstance('Content', 'JTable', array());
			$article->load($article_id);
			$data = array(
			'title' => $app->input->get('title', '', 'STRING'),
			'alias' => $app->input->get('alias', '', 'STRING'),
			'introtext' => $app->input->get('introtext', '', 'STRING'),
			'fulltext' => $app->input->get('fulltext', '', 'STRING'),
			'state' => $app->input->get('state', '', 'INT'),
			'catid' => $app->input->get('catid', '', 'INT'),
			'publish_up' => $app->input->get('publish_up', '', 'STRING'),
			'publish_down' => $app->input->get('publish_down', '', 'STRING'),
			'language' => $app->input->get('language', '', 'STRING')
			);

			// Bind data
			if (!$article->bind($data))
			{
				$obj->success = false;
				$obj->message = $article->getError();

				return $obj;
			}
		}
		else
		{
			$article = JTable::getInstance('content');
			$article->title = $app->input->get('title', '', 'STRING');
			$article->alias = $app->input->get('alias', '', 'STRING');
			$article->introtext = $app->input->get('introtext', '', 'STRING');
			$article->fulltext = $app->input->get('fulltext', '', 'STRING');
			$article->state = $app->input->get('state', '', 'INT');
			$article->catid = $app->input->get('catid', '', 'INT');
			$article->publish_up = $app->input->get('publish_up', '', 'STRING');
			$article->publish_down = $app->input->get('publish_down', '', 'STRING');
			$article->language = $app->input->get('language', '', 'STRING');
		}

		// Check the data.
		if (!$article->check())
		{
			$obj->success = false;
			$obj->message = $article->getError();

			return $obj;
		}

		// Store the data.
		if (!$article->store())
		{
			$obj->success = false;
			$obj->message = $article->getError();

			return $obj;
		}

		$images = json_decode($article->images);

		foreach ($images as $key => $value)
		{
			if ($value)
			{
				$images->$key = JURI::base() . $value;
			}
		}

		$article->images = $images;
		$result = new stdClass;
		$result->results = $article;

		$obj->success = true;
		$obj->{'data'} = $result;

		return $obj;
	}
}
