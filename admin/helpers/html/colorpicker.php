<?php
	/**
	 * @package     Joomla.Libraries
	 * @subpackage  HTML
	 *
	 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
	 * @license     GNU General Public License version 2 or later; see LICENSE
	 */

defined('JPATH_PLATFORM') or die;

/**
 * Utility class for Bootstrap elements.
 *
 * @package     Joomla.Libraries
 * @subpackage  HTML
 * @since       3.0
 */
abstract class JHtmlColorPicker
{
	/**
	 * @var    array  Array containing information for loaded files
	 * @since  3.0
	 */
	protected static $loaded = [];

	/**
	 * Method to load the Bootstrap JavaScript framework into the document head
	 *
	 * If debugging mode is on an uncompressed version of Bootstrap is included for easier debugging.
	 *
	 * @param   mixed  $debug  Is debugging mode on? [optional]
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function framework($debug = null)
	{
		// Only load once
		if (!empty(self::$loaded[__METHOD__]))
		{
			return;
		}

		JHtml::script('media/com_churchdirectory/js/jscolor.min.js');
		self::$loaded[__METHOD__] = true;

		return;
	}
}
