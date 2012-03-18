<?php

/**
 * @version		$Id: churchdirectory.scriptfile.php 71 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;

/**
 * Script file of JFUploader plugin
 */
class plgContentChurchDirectoryInstallerScript {

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

?>
