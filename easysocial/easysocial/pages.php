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

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';

/**
 * API class EasysocialApiResourcePages
 *
 * @since  1.0
 */
class EasysocialApiResourcePages extends ApiResource
{
	/**
	 * Method to get pages list
	 *
	 * @return  mixed
	 *
	 * @deprecated 2.0 use post instead
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->plugin->setResponse(JText::_('PLG_API_EASYSOCIAL_UNSUPPORTED_METHOD_MESSAGE'));
	}

	/**
	 * Method to get pages list
	 *
	 * @return	object|boolean	in success object will return, in failure boolean
	 *
	 * @since 2.0
	 */
	public function get()
	{
		$app = JFactory::getApplication();
		$input = $app->input;
		$filters = $input->get("filters", array(), "ARRAY");
		$user = ES::user();

		$res = new stdclass;
		$res->result = array();
		$res->empty_message = '';

		$limit		=	$app->input->get('limit', 10, 'INT');
		$filters['limit'] = $limit;

		// Set default filters
		$filters['state']	= isset($filters['state']) ? $filters['state'] : SOCIAL_CLUSTER_PUBLISHED;
		$filters['types']	= isset($filters['types']) ? $filters['types'] : $user->isSiteAdmin() ? 'all' : 'user';
		$filters['ordering']	= isset($filters['ordering']) ? $filters['ordering'] : 'latest';

		$model = ES::model('Pages');
		$MappingHelper = new EasySocialApiMappingHelper;

		$pages = $model->getPages($filters);
		$pages = $MappingHelper->mapItem($pages, 'page', Jfactory::getUser()->id);

		if (empty($pages))
		{
			if (! empty($filters['all']))
			{
				$res->empty_message = JText::_('PLG_API_PAGES_EMPTY_ALL');
			}
			elseif (! empty($filters['featured']))
			{
				$res->empty_message = JText::_('PLG_API_PAGES_EMPTY_FEATURED');
			}
			elseif (! empty($filters['uid']))
			{
				$res->empty_message = JText::_('PLG_API_PAGES_EMPTY_CREATED');
			}
			elseif (! empty($filters['liked']))
			{
				$res->empty_message = JText::_('PLG_API_PAGES_EMPTY_LIKE');
			}
			else
			{
				$res->empty_message = JText::_('PLG_API_EASYSOCIAL_PAGE_NOT_FOUND');
			}
		}

		if (! empty($pages))
		{
			$res->empty_message = '';
			$res->result = $pages;
		}

		$this->plugin->setResponse($res);
	}
}
