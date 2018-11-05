<?php
/**
 * @package    Com_Api
 * @copyright  Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
defined('_JEXEC') or die( 'Restricted access' );
require_once JPATH_ADMINISTRATOR . '/components/com_categories/models/categories.php';
jimport('joomla.plugin.plugin');
jimport('joomla.html.html');
JLoader::register('JCategoryNode', JPATH_BASE . '/libraries/legacy/categories/categories.php');

/**
 * Categories Api
 *
 * @since  1.0
 */
class CategoriesApiResourceCategories extends ApiResource
{
	/**
	 * Get categories
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function get()
	{
		$app = JFactory::getApplication();
		$extension = $app->input->get('extension', 'content');
		$this->plugin->setResponse($this->getCategoryList($extension));
	}

	/**
	 * Delete category
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function delete()
	{
		$this->plugin->setResponse('in delete');
	}

	/**
	 * Create category
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function post()
	{
		$this->plugin->setResponse($this->CreateUpdateCategory());
	}

	/**
	 * Get the master query for retrieving a list of categories subject to the mzodel state.
	 *
	 * @param   string  $extension  extension name
	 * 
	 * @return  JDatabaseQuery
	 *
	 * @since   1.6
	 */
	private function getCategoryList($extension)
	{
		$model_categories = JCategories::getInstance($extension);
		$root = $model_categories->get('root');
		$categories = $root->getChildren(true);

		return $categories;
	}

	/**
	 * CreateUpdateCategory is to create / upadte Category
	 *
	 * @return  Bolean
	 *
	 * @since  3.5
	 */
	private function CreateUpdateCategory()
	{
		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			JTable::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
		}

		$obj = new stdclass;

		$app = JFactory::getApplication();
		$cat_id = $app->input->get('id', 0, 'INT');

		if (empty($app->input->get('title', '', 'STRING')))
		{
			$obj->code = 'ER001';
			$obj->message = 'Title Field is Missing';

			return $obj;
		}

		if (empty($app->input->get('extension', '', 'STRING')))
		{
			$obj->code = 'ER002';
			$obj->message = 'Extension Field is Missing';

			return $obj;
		}

		if ($cat_id)
		{
			$category = JTable::getInstance('Content', 'JTable', array());
			$category->load($cat_id);
			$data = array(
			'title' => $app->input->get('title', '', 'STRING'),
		);

			// Bind data
			if (!$cat_id->bind($data))
			{
				$this->setError($article->getError());

				return false;
			}
		}
		else
		{
			$category = JTable::getInstance('content');
			$category->title = $app->input->get('title', '', 'STRING');
			$category->alias = $app->input->get('alias', '', 'STRING');
			$category->description = $app->input->get('description', '', 'STRING');
			$category->published = $app->input->get('published', '', 'STRING');
			$category->parent_id = $app->input->get('parent_id', '', 'STRING');
			$category->extension = $app->input->get('language', '', 'INT');
			$category->access = $app->input->get('catid', '', 'INT');
		}

		// Check the data.
		if (!$category->check())
		{
			$this->setError($category->getError());

			return false;
		}

		// Store the data.
		if (!$category->store())
		{
			$this->setError($category->getError());

			return false;
		}
	}
}
