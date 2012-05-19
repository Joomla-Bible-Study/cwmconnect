<?php

/**
 * @version		$Id: churchdirectory.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Contact component helper.
 *
 * @package	com_churchdirectory
 * @since		1.7.0
 */
class ChurchDirectoryHelper {

    public static $extension = 'com_churchdirectory';

    /**
     * Configure the Linkbar.
     *
     * @param	string	$vName	The name of the active view.
     *
     * @return	void
     * @since	1.7.0
     */
    public static function addSubmenu($vName) {
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_CPANEL'), 'index.php?option=com_churchdirectory&view=cpanel', $vName == 'cpanel'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_CONTACTS'), 'index.php?option=com_churchdirectory&view=churchdirectories', $vName == 'churchdirectories'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_CATEGORIES'), 'index.php?option=com_categories&extension=com_churchdirectory', $vName == 'categories'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_INFO'), 'index.php?option=com_churchdirectory&view=info', $vName == 'info'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_KML'), 'index.php?option=com_churchdirectory&view=kmls', $vName == 'kmls'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_FAMILYUNITS'), 'index.php?option=com_churchdirectory&view=familyunits', $vName == 'familyunits'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_POSITIONS'), 'index.php?option=com_churchdirectory&view=positions', $vName == 'positions'
        );

        if ($vName == 'categories') {
            JToolBarHelper::title(
                    JText::sprintf('COM_CATEGORIES_CATEGORIES_TITLE', JText::_('com_churchdirectory')), 'churchdirectory-categories');
        }
    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @param	int		The category ID.
     * @param	int		The contact ID.
     *
     * @return	JObject
     * @since	1.7.0
     */
    public static function getActions($categoryId = 0, $contactId = 0) {
        $user = JFactory::getUser();
        $result = new JObject;

        if (empty($contactId) && empty($categoryId)) {
            $assetName = 'com_churchdirectory';
        } elseif (empty($contactId)) {
            $assetName = 'com_churchdirectory.category.' . (int) $categoryId;
        } else {
            $assetName = 'com_churchdirectory.churchdirectory.' . (int) $contactId;
        }

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }

}