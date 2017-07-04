<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * ChurchDirectory Component Route Helper
 *
 * @package  ChurchDirectory.Site
 * @since    1.5
 */
abstract class ChurchDirectoryHelperRoute
{
	protected static $lookup;

	/**
	 * Get member route
	 *
	 * @param   integer  $id        The id of the contact
	 * @param   integer  $catid     The id of the contact's category
	 * @param   mixed    $language  The id of the language being used.
	 *
	 * @return string
	 *
	 * @since    1.5
	 */
	public static function getMemberRoute($id, $catid, $language = 0)
	{
		// Create the link
		$link = 'index.php?option=com_churchdirectory&view=member&id=' . $id;

		if ($catid > 1)
		{
			$link .= '&catid=' . $catid;
		}

		if ($language && $language !== '*' && JLanguageMultilang::isEnabled())
		{
			$link .= '&lang=' . $language;
		}

		return $link;
	}

	/**
	 * Get Category Route
	 *
	 * @param   mixed  $catid     The id of the contact's category either an integer id or an instance of JCategoryNode
	 * @param   mixed  $language  The id of the language being used.
	 *
	 * @return string
	 *
	 * @since    1.5
	 */
	public static function getCategoryRoute($catid, $language = 0)
	{
		if ($catid instanceof JCategoryNode)
		{
			$id = $catid->id;
		}
		else
		{
			$id       = (int) $catid;
		}

		if ($id < 1)
		{
			$link = '';
		}
		else
		{
			// Create the link
			$link = 'index.php?option=com_churchdirectory&view=category&id=' . $id;

			if ($language && $language !== '*' && JLanguageMultilang::isEnabled())
			{
				$link .= '&lang=' . $language;
			}
		}

		return $link;
	}
}
