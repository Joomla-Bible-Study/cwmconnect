<?php
/**
 * @version		$Id: churchdirectory.php $
 * @copyright	Copyright (C) 2005 - 2011 All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Contact component helper.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_churchdirectory
 * @since		1.6
 */
class ChurchDirectoryHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param	string	$vName	The name of the active view.
	 *
	 * @return	void
	 * @since	1.6
	 */
	public static function addSubmenu($vName)
	{
		JSubMenuHelper::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_CONTACTS'),
			'index.php?option=com_churchdirectory&view=churchdirectories',
			$vName == 'churchdirectories'
		);
		JSubMenuHelper::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_CATEGORIES'),
			'index.php?option=com_categories&extension=com_churchdirectory',
			$vName == 'categories'
		);
                JSubMenuHelper::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_TOOLS'),
			'index.php?option=com_churchdirectory&controller=tools',
			$vName == 'tools'
		);
                JSubMenuHelper::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_INFO'),
			'index.php?option=com_churchdirectory&view=info',
			$vName == 'info'
		);
                JSubMenuHelper::addEntry(
			JText::_('COM_CHURCHDIRECTORY_SUBMENU_KMLOPTIONS'),
			'index.php?option=com_churchdirectory&view=kmloptions',
			$vName == 'kmloptions'
		);

		if ($vName=='categories') {
			JToolBarHelper::title(
				JText::sprintf('COM_CATEGORIES_CATEGORIES_TITLE',JText::_('com_churchdirectory')),
				'churchdirectory-categories');
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param	int		The category ID.
	 * @param	int		The contact ID.
	 *
	 * @return	JObject
	 * @since	1.6
	 */
	public static function getActions($categoryId = 0, $contactId = 0)
	{
		$user	= JFactory::getUser();
		$result	= new JObject;

		if (empty($contactId) && empty($categoryId)) {
			$assetName = 'com_churchdirectory';
		}
		elseif (empty($contactId)) {
			$assetName = 'com_churchdirectory.category.'.(int) $categoryId;
		}
		else {
			$assetName = 'com_churchdirectory.contact.'.(int) $contactId;
		}

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action) {
			$result->set($action,	$user->authorise($action, $assetName));
		}

		return $result;
	}
}
