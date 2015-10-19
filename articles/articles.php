<?php
/**
 * @package	API
 * @version 1.5
 * @author 	Brian Edgerton
 * @link 	http://www.edgewebworks.com
 * @copyright Copyright (C) 2011 Edge Web Works, LLC. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgAPIArticles extends ApiPlugin
{
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		//load helper file
		require_once JPATH_SITE.'/plugins/api/articles/articles/helper/simpleschema.php';

		ApiResource::addIncludePath(dirname(__FILE__).'/articles');

		// Set resources & access
		$this->setResourceAccess('article', 'public', 'get');
		$this->setResourceAccess('category', 'public', 'get');
		$this->setResourceAccess('latest', 'public', 'get');
	}
}
