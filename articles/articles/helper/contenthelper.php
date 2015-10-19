<?php

/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die('Restricted access');
require_once JPATH_SITE.'/plugins/api/articles/articles/blogs/blog/post.php';
require_once JPATH_SITE.'/plugins/api/articles/articles/blogs/category.php';

class BlogappContentHelper 
{
	public $modules = array();

	public $mods = array();

	public function loadContent(&$article, $position=1,$module=1)
	{

		// Simple performance check to determine whether bot should process further
		if (strpos($article->text, 'loadposition') === false && strpos($article->text, 'loadmodule') === false)
		{
			return true;
		}

		// Expression to search for (positions)
		$regex		= '/{loadposition\s(.*?)}/i';
		//$style		= $this->params->def('style', 'none');
		$style		= 'none';

		// Expression to search for(modules)
		$regexmod	= '/{loadmodule\s(.*?)}/i';
		//$stylemod	= $this->params->def('style', 'none');
		$stylemod	= 'none';

		// Find all instances of plugin and put in $matches for loadposition
		// $matches[0] is full pattern match, $matches[1] is the position
		preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

		// No matches, skip this
		if ($matches)
		{
			foreach ($matches as $match)
			{
				$matcheslist = explode(',', $match[1]);

				// We may not have a module style so fall back to the plugin default.
				if (!array_key_exists(1, $matcheslist))
				{
					$matcheslist[1] = $style;
				}

				$position = trim($matcheslist[0]);
				$style    = trim($matcheslist[1]);

				$output = $this->_load($position, $style);

				// We should replace only first occurrence in order to allow positions with the same name to regenerate their content:
				$article->text = preg_replace("|$match[0]|", addcslashes($output, '\\$'), $article->text, 1);
				$style = 'none';
			}
		}

		// Find all instances of plugin and put in $matchesmod for loadmodule
		preg_match_all($regexmod, $article->text, $matchesmod, PREG_SET_ORDER);

		// If no matches, skip this
		if ($matchesmod)
		{
			foreach ($matchesmod as $matchmod)
			{
				$matchesmodlist = explode(',', $matchmod[1]);

				// We may not have a specific module so set to null
				if (!array_key_exists(1, $matchesmodlist))
				{
					$matchesmodlist[1] = null;
				}

				// We may not have a module style so fall back to the plugin default.
				if (!array_key_exists(2, $matchesmodlist))
				{
					$matchesmodlist[2] = $stylemod;
				}

				$module = trim($matchesmodlist[0]);
				$name   = htmlspecialchars_decode(trim($matchesmodlist[1]));
				$stylemod  = trim($matchesmodlist[2]);

				// $match[0] is full pattern match, $match[1] is the module,$match[2] is the title
				$output = $this->_loadmod($module, $name, $stylemod);

				// We should replace only first occurrence in order to allow positions with the same name to regenerate their content:
				$article->text = preg_replace("|$matchmod[0]|", addcslashes($output, '\\$'), $article->text, 1);
				//$stylemod = $this->params->def('style', 'none');
				$stylemod = 'none';
			}
		}
		//return $item;
	}
	
	/**
	 * Loads and renders the module
	 *
	 * @param   string  $position  The position assigned to the module
	 * @param   string  $style     The style assigned to the module
	 *
	 * @return  mixed
	 *
	 * @since   1.6
	 */
	public function _load($position, $style = 'none')
	{
		$this->modules[$position] = '';
		$document	= JFactory::getDocument();
		$document->setType('html');
		$renderer	= $document->loadRenderer('module');
		$modules	= JModuleHelper::getModules($position);
		$params		= array('style' => $style);
		ob_start();

		foreach ($modules as $module)
		{
			echo $renderer->render($module, $params);
		}

		$this->modules[$position] = ob_get_clean();

		return $this->modules[$position];
	}

	/**
	 * This is always going to get the first instance of the module type unless
	 * there is a title.
	 *
	 * @param   string  $module  The module title
	 * @param   string  $title   The title of the module
	 * @param   string  $style   The style of the module
	 *
	 * @return  mixed
	 *
	 * @since   1.6
	 */
	public function _loadmod($module, $title, $style = 'none')
	{
		$this->mods[$module] = '';
		$document	= JFactory::getDocument();
		$document->setType('html');
		$renderer	= $document->loadRenderer('module');
		$mod		= JModuleHelper::getModule($module, $title);

		// If the module without the mod_ isn't found, try it with mod_.
		// This allows people to enter it either way in the content
		if (!isset($mod))
		{
			$name = 'mod_' . $module;
			$mod  = JModuleHelper::getModule($name, $title);
		}

		$params = array('style' => $style);
		ob_start();

		echo $renderer->render($mod, $params);

		$this->mods[$module] = ob_get_clean();

		return $this->mods[$module];
	}
	
	public function mapCategory( $cat )
	{
		return $item;
	}
	
}


