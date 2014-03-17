<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::register('JHtmlDropdown', JPATH_SITE . '/libraries/cms/html/dropdown.php');
/**
 * class to help with geoupdate
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class JHtmlGeoUpdate extends JHtmlDropdown
{

	/**
	 * Append a featured item to the current dropdown menu
	 *
	 * @param   integer  $id          Record ID
	 * @param   string   $prefix      Task prefix
	 * @param   string   $customLink  The custom link if dont use default Joomla action format
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public static function update($id, $prefix = '', $customLink = '')
	{
		if (!$customLink)
		{
			$option = JFactory::getApplication()->input->getCmd('option', 'com_churchdirectory');
			$link   = 'index.php?option=' . $option;
		}
		else
		{
			$link = $customLink;
		}

		$link .= '&view=geoupdate&id=' . $id . '&tmpl=component';
		$link = JRoute::_($link);

		self::addCustomItem(JText::_('COM_CHURCHDIRECTORY_GEOUPDATE'), $link, 'class="modal" rel="{handler: \'iframe\', size: {x: 600, y: 250}}"');

		return;
	}

}
