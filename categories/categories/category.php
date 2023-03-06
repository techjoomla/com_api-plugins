<?php
/**
 * @package     API
 * @subpackage  com_api
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

if (JVERSION < '4.0.0')
{
	Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_categories/tables');
	BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/models/');		
}
else
{
	BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_categories/src/Model/');			
}

/**
 * Category API resource class
 *
 * @package  API
 * @since    1.6.0
 */
class CategoriesApiResourceCategory extends ApiResource
{
	/**
	 * Get categories
	 *
	 * @return  object  Category details wrapped inside standard api response wrapper
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getCategory());
	}

	/**
	 * Save category - create / update
	 *
	 * @return  object  Category details wrapped inside standard api response wrapper
	 */
	public function post()
	{
		// $this->plugin->setResponse($this->saveCategory());
	}

	/**
	 * Get category details
	 *
	 * @return  array
	 *
	 * @since   1.6.0
	 */
	public function getCategory()
	{		
		// Set application parameters in model
		$app   = Factory::getApplication();
		$input = $app->input;
		
		// get an instance of the category model
		$model = BaseDatabaseModel::getInstance('Category', 'CategoriesModel');
		
		// Important to get from input directly and not from $input->get->get()
		$id = $input->get('id', 0, 'int');

		if (!$id)
		{
			// Not Found Error sets HTTP 404						
			ApiError::raiseError(404, Text::_('PLG_API_CATEGORIES_RECORD_NOT_FOUND_MESSAGE'), 'APINotFoundException');
		}

		$item = $model->getItem($id);

		// Exists?
		if (empty($item->id))
		{
			// Not Found Error sets HTTP 404
			ApiError::raiseError(404, Text::_('PLG_API_CATEGORIES_RECORD_NOT_FOUND_MESSAGE'), 'APINotFoundException');
		}

		return $item;
	}

	/**
	 * CreateUpdateCategory is to create / upadte Category
	 *
	 * @return  Bolean
	 *
	 * @since  3.5
	 */
	/*public function saveCategory()
	{
		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			Table::addIncludePath(JPATH_PLATFORM . 'joomla/database/table');
		}

		$obj = new stdclass;

		$app = Factory::getApplication();
		$cat_id = $app->input->post->get('id', 0, 'INT');

		if (empty($app->input->post->get('title', '', 'STRING')))
		{
			$obj->code = 'ER001';
			$obj->message = 'Title Field is Missing';

			return $obj;
		}

		if (empty($app->input->post->get('extension', '', 'STRING')))
		{
			$obj->code = 'ER002';
			$obj->message = 'Extension Field is Missing';

			return $obj;
		}

		if ($cat_id)
		{
			$category = Table::getInstance('Content', 'Table', array());
			$category->load($cat_id);
			$data = array(
				'title' => $app->input->post->get('title', '', 'STRING'),
			);

			if (!$cat_id->bind($data))
			{
				$this->setError($article->getError());

				return false;
			}
		}
		else
		{
			$category = Table::getInstance('content');
			$category->title = $app->input->post->get('title', '', 'STRING');
			$category->alias = $app->input->post->get('alias', '', 'STRING');
			$category->description = $app->input->post->get('description', '', 'STRING');
			$category->published = $app->input->post->get('published', '', 'STRING');
			$category->parent_id = $app->input->post->get('parent_id', '', 'STRING');
			$category->extension = $app->input->post->get('language', '', 'INT');
			$category->access = $app->input->post->get('catid', '', 'INT');
		}

		if (!$category->check())
		{
			$this->setError($category->getError());

			return false;
		}

		if (!$category->store())
		{
			$this->setError($category->getError());

			return false;
		}
	}*/
}
