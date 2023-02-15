<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_api
 *
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://techjoomla.com
 * Work derived from the original RESTful API by Techjoomla (https://github.com/techjoomla/Joomla-REST-API)
 * and the com_api extension by Brian Edgerton (http://www.edgewebworks.com)
 */

defined('JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/stream.php';
require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/models/hashtags.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/plugins/api/easysocial/libraries/uploadHelper.php';

/**
 * API class PlgAPIEasysocial
 *
 * @since  1.0
 */
class EasysocialApiResourceHashtag extends ApiResource
{
	/**
	 * Method description
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->plugin->setResponse($this->get_hash_list());
	}

	/**
	 * Method get hashtag list
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get_hash_list()
	{
		// Search for hashtag
		$app	=	Factory::getApplication();

		// Accepting input
		$word	=	$app->input->get('tag', null, 'STRING');
		$obj	=	new EasySocialModelHashtags;

		// Calling method and return result
		$result	=	$obj->search($word);

		return $result;
	}
}
