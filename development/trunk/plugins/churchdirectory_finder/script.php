<?php

/**
 * Install ChurchDirectory Finder
 * @package             Finder.ChurchDirectory
 *
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;

/**
 * Script file of ChurchDirectory Finder plugin
 * @package Finder.ChurchDirectory
 * @since 1.7.2
 */
class plgFinderChurchDirectoryInstallerScript {

    /**
     * Install function
     * @param string $parent
     */
    function install($parent) {
        // I activate the plugin
        $db = JFactory::getDbo();
        $tableExtensions = $db->nameQuote("#__extensions");
        $columnElement = $db->nameQuote("element");
        $columnType = $db->nameQuote("type");
        $columnEnabled = $db->nameQuote("enabled");

        // Enable plugin
        $db->setQuery("UPDATE $tableExtensions SET $columnEnabled=1 WHERE $columnElement='churchdirectory' AND $columnType='plugin'");
        $db->query();

        echo '<p>' . JText::_('CHURCHDIRECTORY_PLUGIN_ENABLED') . '</p>';
    }

}