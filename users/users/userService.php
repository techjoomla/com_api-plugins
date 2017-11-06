<?php
/**
 * @version    SVN: <svn_id>
 * @package    com_api.plugins
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
// No direct access.
defined('_JEXEC') or die;

require_once JPATH_ROOT . '/administrator/components/com_users/models/users.php';

/**
 * User Api.
 *
 * @package     com_api.plugins
 * @subpackage  plugins
 *
 * @since       1.0
 */
class UsersModelUsersSearch extends UsersModelUsers
{
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.6
	 */
	public function getListQuery()
	{
		$app = JFactory::getApplication();
		$getReqBody = json_decode($app->get('reqBody'));

		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select',
				'a.id, a.name, a.username, a.email, a.registerDate'
			)
		);

		$query->from($db->quoteName('#__users') . ' AS a');

		// Check user search filter
		$search = isset($getReqBody->request->search) ? $getReqBody->request->search : false;

		if (!empty($search))
		{
			$search = ('%' . str_replace(' ', '%', trim($search) . '%'));

			$query->where($db->quoteName('a.name') . ' LIKE ' . $db->quote($search));
		}

		// Check user block filter
		$block = isset($getReqBody->request->filters->block) ? (array) $getReqBody->request->filters->block : array();

		if (!empty($block))
		{
			foreach ($block as &$blockValue)
			{
				$blockValue = 'a.block = ' . (int) $blockValue;
			}

			$query->where('(' . implode(' OR ', $block) . ')');
		}

		// Check user id filter
		$userIds = isset($getReqBody->request->filters->id) ? (array) $getReqBody->request->filters->id : array();

		if (!empty($userIds))
		{
			foreach ($userIds as &$userId)
			{
				$userId = 'a.id = ' . (int) $userId;
			}

			$query->where('(' . implode(' OR ', $userIds) . ')');
		}

		// Check user email filter
		$userEmails = isset($getReqBody->request->filters->email) ? (array) $getReqBody->request->filters->email : array();

		if (!empty($userEmails))
		{
			foreach ($userEmails as &$userEmail)
			{
				$userEmail = 'a.email = ' . $db->quote($userEmail);
			}

			$query->where('(' . implode(' OR ', $userEmails) . ')');
		}

		// Check user username filter
		$usernames = isset($getReqBody->request->filters->username) ? (array) $getReqBody->request->filters->username : array();

		if (!empty($usernames))
		{
			foreach ($usernames as &$username)
			{
				$username = 'a.username = ' . $db->quote($username);
			}

			$query->where('(' . implode(' OR ', $usernames) . ')');
		}

		// Check list ordering filter
		if (isset($getReqBody->request->sort_by->name))
		{
			$orderColumn = 'name';
			$ordering = $getReqBody->request->sort_by->name;
		}
		elseif (isset($getReqBody->request->sort_by->id))
		{
			$orderColumn = 'id';
			$ordering = $getReqBody->request->sort_by->id;
		}
		elseif (isset($getReqBody->request->sort_by->email))
		{
			$orderColumn = 'email';
			$ordering = $getReqBody->request->sort_by->email;
		}
		elseif (isset($getReqBody->request->sort_by->username))
		{
			$orderColumn = 'username';
			$ordering = $getReqBody->request->sort_by->username;
		}
		else
		{
			$ordering = array();
		}

		if (!empty($ordering))
		{
			$query->order('a.' . $orderColumn . ' ' . $ordering);
		}

		// Check offset & limit filter
		$offset = isset($getReqBody->request->offset) ? $getReqBody->request->offset : array();
		$limit = isset($getReqBody->request->limit) ? $getReqBody->request->limit : array();

		$query->setLimit((int) $limit, (int) $offset);

		return $query;
	}
}
