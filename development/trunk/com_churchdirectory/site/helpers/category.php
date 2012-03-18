<?php
/**
 * @version		$Id: category.php 21097 2011-04-07 15:38:03Z dextercowley $
 * @package		Joomla.Site
 * @subpackage	com_content
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

// Component Helper
jimport('joomla.application.component.helper');
jimport('joomla.application.categories');

/**
 * ChurchDirectory Component Category Tree
 *
 * @static
 * @package		Joomla.Site
 * @subpackage	com_churchdirectory
 * @since 1.6
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
