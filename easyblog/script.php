<?php

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
			JFile::move(JPATH_SITE.'/plugins/api/easyblog/components/com_easyblog/helpers/simpleschema.php', JPATH_SITE.'/components/com_easyblog/helpers/simpleschema.php');
			JFolder::delete(JPATH_SITE.'/plugins/api/easyblog/components');
		}
		return true;
	}
}
