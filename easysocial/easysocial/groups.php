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

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

// @TODO - must be include at bootsraping
JLoader::register("EasySocialApiMappingHelper", JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php');

/**
 * API class EasysocialApiResourceGroups
 *
 * @since  1.0
 */
class EasysocialApiResourceGroups extends ApiResource
{
	/**
	 * Method to get groups list
	 *
	 * @return  mixed
	 *
	 * @deprecated 2.0 use post instead
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$app = JFactory::getApplication();
		$mygroups = $app->input->get('mygroups', false, 'BOOLEAN');
		$inputArray = $app->input->getArray();

		// Set values for post
		foreach ($inputArray as $key => $value)
		{
			$app->input->post->set($key, $value);
		}

		// Special case for my groups
		if ($mygroups)
		{
			$app->input->post->set('mine', true);
		}

		$this->post();
	}

	/**
	 * Method to get groups list
	 *
	 * @return  ApiPlugin response object
	 *
	 * @since 2.0
	 */
	public function post()
	{
		$app		=	JFactory::getApplication();
		$input		=	$app->input;
		$filters	=	$input->post->getArray();
		$user		=	ES::user();

		$apiResponse = new stdclass;
		$apiResponse->result = array();
		$apiResponse->empty_message = JText::_('PLG_API_GROUPS_EMPTY_ALL');

		$options['limit'] = $app->input->get('limit', 10, 'INT');

		// Set default filters
		$options['state'] = isset($filters['state']) ? $filters['state'] : SOCIAL_CLUSTER_PUBLISHED;
		$options['types'] = isset($filters['types']) ? $filters['types'] : $user->isSiteAdmin() ? 'all' : 'user';
		$options['ordering'] = isset($filters['ordering']) ? $filters['ordering'] : 'latest';

		$model = ES::model('Groups');
		$MappingHelper = new EasySocialApiMappingHelper;
		$groups = array();

		if (isset($filters['mine']))
		{
			$options['userid'] = $user->id;
			$options['types'] = 'participated';
			$options['featured'] = '';
			$apiResponse->empty_message = JText::_('PLG_API_GROUPS_EMPTY_CREATED');
		}
		elseif (isset($filters['invited']))
		{
			$options['invited'] = $user->id;
			$options['types'] = 'all';
			$apiResponse->empty_message = JText::_('PLG_API_GROUPS_EMPTY_INVITED');
		}
		elseif (isset($filters['pending']))
		{
			$options['uid'] = $user->id;
			$options['state'] = SOCIAL_CLUSTER_DRAFT;
			$options['types'] = 'user';
			$apiResponse->empty_message = JText::_('PLG_API_CLUSTER_NO_PENDING_MODERATION_GROUP');
		}
		elseif (isset($filters['featured']))
		{
			$options['featured'] = true;
			$apiResponse->empty_message = JText::_('PLG_API_GROUPS_EMPTY_FEATURED');
		}
		elseif (isset($filters['participated']) && $user->id)
		{
			$options['userid'] = $user->id;
			$options['types'] = 'participated';
		}
		elseif (isset($filters['category']))
		{
			$categoryId = $filters['category'];
			$category = ES::table('GroupCategory');
			$category->load($categoryId);

			// Check if this category is a container or not
			if ($category->container)
			{
				// Get all child ids from this category
				$categoryModel = ES::model('ClusterCategory');
				$childs = $categoryModel->getChildCategories($category->id);

				$childIds = array();

				foreach ($childs as $child)
				{
					$childIds[] = $child->id;
				}

				// If the childs is empty, we assign the parent itself
				if (empty($childIds))
				{
					$options['category'] = $categoryId;
				}
				else
				{
					$options['category'] = $childIds;
				}
			}
			else
			{
				$options['category'] = $categoryId;
			}

			$apiResponse->empty_message = JText::_('PLG_API_GROUPS_EMPTY_CATEGORY');
		}

		$groups	=	$model->getGroups($options);

		$groups = $MappingHelper->mapItem($groups, 'group', $user->id);

		if (! empty($groups))
		{
			$apiResponse->empty_message = '';
			$apiResponse->result = $groups;
		}

		$this->plugin->setResponse($apiResponse);
	}
}
