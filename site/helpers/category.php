<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * ChurchDirectory Component Category Tree
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryCategories extends JCategories
{
	/**
	 * Constructor Helper
	 *
	 * @param   array  $options  Array of options
	 *
	 * @since    1.5
	 */
	public function __construct($options = [])
	{
		$options['table']      = '#__churchdirectory_details';
		$options['extension']  = 'com_churchdirectory';
		$options['statefield'] = 'published';
		parent::__construct($options);
	}
}
