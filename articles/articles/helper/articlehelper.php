<?php

/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die('Restricted access');

class ArticleContentHelper 
{
	public function __construct()
	{
		$this->app			= JFactory::getApplication();
		$this->database		= JFactory::getDBO();
	}
	
	public function getArticleIdByAlias($article_alias)
	{
		$query = $this->database->getQuery(true);
		$query->select($this->database->quoteName(array('id')));
		$query->from($this->database->quoteName('#__content'));
		$query->where($this->database->quoteName('alias') . ' = '. $this->database->quote(trim($article_alias)));

		$this->database->setQuery($query);

		return $this->database->loadResult();
	}
}


