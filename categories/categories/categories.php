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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory; 
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\InputFilter; 
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
 * Categories API resource class
 *
 * @package  API
 * @since    1.6.0
 */
class CategoriesApiResourceCategories extends ApiResource
{
	/**
	 * Get categories
	 *
	 * @return  object  Categories list wrapped inside standard api response wrapper
	 */
	public function get()
	{
		$this->plugin->setResponse($this->getCategoriesList());
	}

	/**
	 * Get list of categories based on input params
	 *
	 * @return  array
	 *
	 * @since   1.6.0
	 */
	public function getCategoriesList()
	{
		// Set application parameters in model
		$app   = Factory::getApplication();
		$input = $app->input;
		
		$model = BaseDatabaseModel::getInstance('Categories', 'CategoriesModel');

		// Get inputs params
		$limit      = $input->get->get('limit', 20, 'int');
		$limitStart = $input->get->get('limitstart', 0, 'int');
		$search     = $input->get->get('search', '', 'string');

		// Get filters
		$filters      = $input->get->get('filters', '', 'array');
		$jInputFilter = InputFilter::getInstance();

		// Cleanup and set default values
		$access    = isset($filters['access'])   ? $jInputFilter->clean($filters['access'], 'cmd') : '';
		$extension = isset($filters['extension']) ? $jInputFilter->clean($filters['extension'], 'cmd') : 'com_content';
		$language  = isset($filters['language'])  ? $jInputFilter->clean($filters['language'], 'string') : '';
		$level     = isset($filters['level'])     ?  $jInputFilter->clean($filters['level'], 'string') : '';
		$published = isset($filters['published']) ? $jInputFilter->clean($filters['published'], 'int') : 1;

		// Set the filters based on the module params
		$model->setState('list.limit', $limit);
		$model->setState('list.start', $limitStart);
		$model->setState('filter.search', $search);

		$model->setState('filter.access', $access);
		$model->setState('filter.extension', $extension);
		$model->setState('filter.language', $language);
		$model->setState('filter.level', $level);
		$model->setState('filter.published', $published);

		// Extract the component name, Extract the optional section name
		$parts = explode('.', $extension);
		$model->setState('filter.component', $parts[0]);
		$model->setState('filter.section', (count($parts) > 1) ? $parts[1] : null);

		$rows = $model->getItems();
		$obj = new stdclass;
		if (count($rows) > 0)
		{			
			$obj->success = true;
			$obj->data = $rows;

			return $obj;
		}
		else
		{
			$obj->success = false;
			$obj->message = Text::_('PLG_API_CATEGORIES_CATEGORIES_NOT_FOUND');
		}
		return $obj;
	}
}
