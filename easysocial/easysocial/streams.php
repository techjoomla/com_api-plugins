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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

ES::import('site:/controllers/controller');

/**
 * API class EasysocialApiResourceStreams
 *
 * @since  1.0
 */
class EasysocialApiResourceStreams extends ApiResource
{
	/**
	 * get
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
		public function get()
		{
			$this->plugin->setResponse(Text::_('PLG_API_EASYSOCIAL_USE_POST_METHOD_MESSAGE'));
		}

	/**
	 * post stream
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function post()
	{
		$this->plugin->setResponse($this->processAction());
	}

	/**
	 * post stream
	 *
	 * @return  mixed
	 *
	 * @since 1.0
	 */
		private function processAction()
		{
			$app = Factory::getApplication();
			$target_id = $app->input->get('target_id', 0, 'INT');
			$action = $app->input->get('action', 0, 'STRING');
			$uid = $app->input->get('uid', 0, 'INT');

			switch ($action)
			{
				case 'hide'		:
									return $res = $this->hide($target_id);
									break;
				case 'unhide'	:
									return $res = $this->unhide($target_id);
									break;
				case 'delete'	:
									return $res = $this->delete($target_id);
									break;
				case 'bookmark' :
									return $res = $this->bookmark($target_id, $uid);
									break;
			}
		}

	/**
	 * get videos throught api
	 *
	 * @param   string  $target_id  The target id.
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function hide($target_id)
	{
		$res = new stdClass;

		// If id is null, throw an error.
		if (!$target_id)
		{
			ApiError::raiseError(403, Text::_('COM_EASYSOCIAL_ERROR_UNABLE_TO_LOCATE_ID'));
		}

		// Get logged in user
		$my = ES::user();

		// Load the stream item.
		$item = ES::table('Stream');
		$item->load($target_id);

		// Check if the user is allowed to hide this item
		if ( !$item->hideable() )
		{
			ApiError::raiseError(403, Text::_('COM_EASYSOCIAL_STREAM_NOT_ALLOWED_TO_HIDE'));
		}

		// Get the model
		$model = ES::model('Stream');
		$state = $model->hide($target_id, $my->id);

		if ($state)
		{
			$res->success = 1;
			$res->message = Text::_('COM_EASYSOCIAL_STREAM_ITEM_HIDDEN_SUCCESS');
		}
		else
		{
			ApiError::raiseError(403, Text::_('PLG_API_EASYSOCIAL_HIDE_NEWSFEED_ITEM_ERROR'));
		}

		return $res;
	}

	/**
	 * hide stream
	 *
	 * @param   string  $target_id  The target id.
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	private function unhide($target_id)
	{
		$res = new stdClass;

		// If id is null, throw an error.
		if (!$target_id)
		{
			ApiError::raiseError(403, Text::_('COM_EASYSOCIAL_ERROR_UNABLE_TO_LOCATE_ID'));
		}

		// Get logged in user
		$my = ES::user();

		// Load the stream item.
		$item = ES::table('Stream');
		$item->load($target_id);

		// Check if the user is allowed to hide this item
		if ( !$item->hideable())
		{
			ApiError::raiseError(403, Text::_('PLG_API_EASYSOCIAL_STREAM_NOT_ALLOWED_TO_UNHIDE'));
		}

		// Get the model
		$model = ES::model('Stream');
		$state = $model->unhide($target_id, $my->id);

		if ($state)
		{
			$res->success = 1;
			$res->message = Text::_('PLG_API_EASYSOCIAL_UNHIDE_NEWSFEED_ITEM');
		}
		else
		{
			ApiError::raiseError(403, Text::_('PLG_API_EASYSOCIAL_UNHIDE_NEWSFEED_ITEM_ERROR'));
		}

		return $res;
	}

	/**
	 * delete stream
	 *
	 * @param   string  $target_id  The target id.
	 * 
	 * @return  mixed
	 *
	 * @since 1.0
	 */
	public function delete($target_id)
	{
		$res = new stdClass;

		if (!$target_id)
		{
			ApiError::raiseError(403, Text::_('COM_EASYSOCIAL_ERROR_UNABLE_TO_LOCATE_ID'));
		}

		// Load the stream item.
		$item = ES::table('Stream');
		$item->load($target_id);

		$my = ES::user();
		$access = $my->getAccess();

		// If the user is not a super admin, we need to check their privileges
		if (!$my->isSiteAdmin())
		{
			// Check if the stream item is for groups
			if ($item->cluster_id)
			{
				if ($item->cluster_type == 'group')
				{
					$cluster = ES::group($item->cluster_id);
				}

				if ($item->cluster_type == 'event')
				{
					$cluster = ES::event($item->cluster_id);
				}

				if (!$cluster->isClient("administrator") && !$access->allowed('stream.delete', false))
				{
					ApiError::raiseError(403, Text::_('COM_EASYSOCIAL_STREAM_NOT_ALLOWED_TO_DELETE'));
				}
			}
			else
			{
				if (!$access->allowed('stream.delete', false))
				{
					ApiError::raiseError(403, Text::_('COM_EASYSOCIAL_STREAM_NOT_ALLOWED_TO_DELETE'));
				}
			}
		}

		$state = $item->delete();

		if ($state)
		{
			$res->success = 1;
			$res->message = Text::_('PLG_API_EASYSOCIAL_DELETE_NEWSFEED_ITEM');
		}
		else
		{
			ApiError::raiseError(400, Text::_('PLG_API_EASYSOCIAL_DELETE_NEWSFEED_ITEM_ERROR'));
		}

		return $res;
	}
}
