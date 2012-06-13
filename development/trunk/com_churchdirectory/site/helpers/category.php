<?php
/**
 * @package	ChurchDirectory.Site
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

// Component Helper
jimport('joomla.application.component.helper');
jimport('joomla.application.categories');

/**
 * ChurchDirectory Component Category Tree
 */

class ChurchDirectoryCategories extends JCategories
{
	public function __construct($options = array())
	{
		$options['table'] = '#__churchdirectory_details';
		$options['extension'] = 'com_churchdirectory';
		$options['statefield'] = 'published';
		parent::__construct($options);
	}
}
