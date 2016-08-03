<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

//Script class
class plgapieasyblogInstallerScript
{
	public function postflight($type, $parent)
	{
		//If type is install
		if ($type == 'install')
		{
			//Move library file to Joomla libraries and delete it from plugin			
			JFolder::move(JPATH_SITE.'/plugins/api/easyblog/libraries/simpleschema', JPATH_SITE.'/libraries/simpleschema');
			JFolder::delete(JPATH_SITE.'/plugins/api/easyblog/libraries');

			//Move helper file to easyblog helpers and delete it from plugin			
			//JFile::move(JPATH_SITE.'/plugins/api/easyblog/components/com_easyblog/helpers/simpleschema.php', JPATH_SITE.'/components/com_easyblog/helpers/simpleschema.php');
			//JFolder::delete(JPATH_SITE.'/plugins/api/easyblog/components');
			
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);

			$fields = array(
			$db->quoteName('enabled') . ' = ' . (int) 1,
			$db->quoteName('ordering') . ' = ' (int) 9999
			);

			$conditions = array(
			$db->quoteName('name') . ' = ' . $db->quote('Api - Easyblog'), 
			$db->quoteName('type') . ' = ' . $db->quote('plugin')
			);

			$query->update($db->quoteName('#__extensions'))->set($fields)->where($conditions);

			$db->setQuery($query);   
			$db->execute();	
		}
		return true;
	}
}
