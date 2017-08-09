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
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');
jimport('joomla.html.html');

require_once JPATH_SITE . '/plugins/api/easysocial/libraries/mappingHelper.php';
require_once JPATH_SITE . '/components/com_easysocial/controllers/videos.php';
/**
 * API class EasysocialApiResourceRemoveTags
 *
 * @since  1.0
 */
class EasysocialApiResourceRemoveTags extends ApiResource
{
	/**
	 * Method get
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function get()
	{
		$this->plugin->err_code = 405;
		$this->plugin->err_message = JText::_('PLG_API_EASYSOCIAL_USE_POST_METHOD_MESSAGE');
		$this->plugin->setApiResponse(null);
	}

	/**
	 * Method post
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$app	=	JFactory::getApplication();

		// Get the tag id
		$id		=	$app->input->get('friends_tagsid', 0, 'int');

		// Get the tag
		$tag	=	ES::table('Tag');
		$tag->load($id);

		// Check for permissions to delete this tag
		$table	=	ES::table('Video');
		$table->load($tag->target_id);

		$video	=	ES::video($table->uid, $table->type, $table);

		// Delete the tag
		$tag->delete();
		$video			=	ES::video();
		$video->load($tag->target_id);
		$tag_peoples	=	$video->getTags();

		$res				=	array();
		$dataObj			=	new stdClass;
		$dataObj->results		=	$tag_peoples;
		$dataObj->empty_message		=	'';
		$res['data']			=	$dataObj;
		$res['err_code']		=	200;
		$res['err_message']		=	'';

		$this->plugin->setResponse($res);
	}
}
