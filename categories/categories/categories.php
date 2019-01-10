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

// Load category list model from admin
JLoader::register('CategoriesModelCategories', JPATH_ROOT . '/administrator/components/com_categories/models/categories.php');

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
		// Get an instance of the generic articles model
		$model = JModelLegacy::getInstance('Categories', 'CategoriesModel', array('ignore_request' => true));

		// Set application parameters in model
		$app   = JFactory::getApplication();
		$input = $app->input;

		// Get inputs params
		$limit      = $input->get->get('limit', 20, 'int');
		$limitStart = $input->get->get('limitstart', 0, 'int');
		$search     = $input->get->get('search', '', 'string');

		// Get filters
		$filters      = $input->get->get('filters', '', 'array');
		$jInputFilter = JFilterInput::getInstance();

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

		return $model->getItems();
	}
}
