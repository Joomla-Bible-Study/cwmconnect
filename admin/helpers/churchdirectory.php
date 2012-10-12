<?php

/**
 * ChurchDirectory Helper
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Contact component helper.
 *
 * @package	ChurchDirectory.Admin
 * @since		1.7.0
 */
class ChurchDirectoryHelper {

    /**
     * Set Extension Name
     * @var string
     */
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
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_MEMBERS'), 'index.php?option=com_churchdirectory&view=members', $vName == 'members'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_CATEGORIES'), 'index.php?option=com_categories&extension=com_churchdirectory', $vName == 'categories'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_INFO'), 'index.php?option=com_churchdirectory&view=info', $vName == 'info'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_KML'), 'index.php?option=com_churchdirectory&task=kml.edit&id=1', $vName == 'kmls'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_FAMILYUNITS'), 'index.php?option=com_churchdirectory&view=familyunits', $vName == 'familyunits'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_DIRHEADERS'), 'index.php?option=com_churchdirectory&view=dirheaders', $vName == 'dirheaders'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_POSITIONS'), 'index.php?option=com_churchdirectory&view=positions', $vName == 'positions'
        );
        JSubMenuHelper::addEntry(
                JText::_('COM_CHURCHDIRECTORY_SUBMENU_DATABASE'), 'index.php?option=com_churchdirectory&view=database', $vName == 'database'
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
            $level = 'component';
        } elseif (empty($contactId)) {
            $assetName = 'com_churchdirectory.category.' . (int) $categoryId;
            $level = 'category';
        } else {
            $assetName = 'com_churchdirectory.members.' . (int) $contactId;
            $level = 'category';
        }

        $actions = JAccess::getActions('com_churchdirectory', $level);

        foreach ($actions as $action) {
            $result->set($action->name, $user->authorise($action->name, $assetName));
        }

        return $result;
    }

    /**
     * Get Age of Member
     * @param string $birthdate
     * @return string
     * @internal requires php5.3.x
     * @since 1.7.3
     */
    public static function getAge($birthdate) {
        if (!empty($birthdate)):
            $date = new DateTime($birthdate);
            $now = new DateTime();
            $interval = $now->diff($date);
            if ($interval->y !== intval(date('Y'))):
                return $interval->y;
            else:
                return '0';
            endif;
        endif;
    }

}
