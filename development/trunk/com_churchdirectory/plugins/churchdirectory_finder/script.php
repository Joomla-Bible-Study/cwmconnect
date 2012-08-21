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
     * The release value to be displayed and check against throughout this file.
     *
     * @var string
     */
    private $release = '1.7.2';

    /**
     * Find mimimum required joomla version for this extension. It will be read from the version attribute (install tag) in the manifest file
     *
     * @var string
     */
    private $minimum_joomla_release = '2.5.0';

    /**
     * preflight runs before anything else and while the extracted files are in the uploaded temp folder.
     * If preflight returns false, Joomla will abort the update and undo everything already done.
     *
     * @param string $type is the type of change (install, update or discover_install, not uninstall).
     * @param string $parent is the class calling this method.
     * @return boolean
     */
    function preflight($type, $parent) {
        // this component does not work with Joomla releases prior to 1.7
        // abort if the current Joomla release is older
        $jversion = new JVersion();

        // Extract the version number from the manifest. This will overwrite the 1.0 value set above
        $this->release = $parent->get("manifest")->version;

        // Find mimimum required joomla version
        $this->minimum_joomla_release = $parent->get("manifest")->attributes()->version;

        if (version_compare($jversion->getShortVersion(), $this->minimum_joomla_release, 'lt')) {
            Jerror::raiseWarning(null, 'Cannot install plg_churchdirectory_finder in a Joomla release prior to ' . $this->minimum_joomla_release);
            return false;
        }

        // abort if the component being installed is not newer than the currently installed version
        if ($type == 'update') {
            $oldRelease = $this->getParam('version');
            $rel = $oldRelease . ' to ' . $this->release;
            if (version_compare($this->release, $oldRelease, 'le')) {
                Jerror::raiseWarning(null, 'Incorrect version sequence. Cannot upgrade ' . $rel);
                return false;
            }
        }
    }

    /**
     * Install function
     * @param string $parent
     */
    function install($parent) {

    }

    /**
     * uninstall runs before any other action is taken (file removal or database processing)
     * @param string $parent
     */
    function uninstall($parent) {
        echo '<p>' . JText::_('PLG_CHURCHDIRECTORY_FINDER_UNINSTALL ' . $this->release) . '</p>';
    }

    /**
     * postflight is run after the extension is registered in the database.
     * @param type $type is the type of change (install, update or discover_install, not uninstall).
     * @param type $parent is the class calling this method.
     */
    function postflight($type, $parent) {
        // set initial values for component parameters
        $params['my_param0'] = 'Component version ' . $this->release;
        $params['my_param1'] = 'Another value';
        $params['my_param2'] = 'Still yet another value';
        $this->setParams($params);

        // I activate the plugin
        $db = JFactory::getDbo();
        $tableExtensions = $db->nameQuote("#__extensions");
        $columnElement = $db->nameQuote("element");
        $columnType = $db->nameQuote("type");
        $columnEnabled = $db->nameQuote("enabled");
        $columnFolder = $db->nameQuote("folder");

        // Enable plugin
        $db->setQuery("UPDATE $tableExtensions SET $columnEnabled=1 WHERE $columnElement='churchdirectory' AND $columnType='plugin' AND $columnFolder='finder'");
        $db->query();

        echo '<p>' . JText::_('PLG_CHURCHDIRECTORY_FINDER_POSTFLIGHT') . ' ' . $type . ' to ' . $this->release . '</p><p>' . JText::_('PLG_CHURCHDIRECTORY_FINDER_ENABLED') . '</p>';
    }

    /**
     * get a variable from the manifest file (actually, from the manifest cache).
     * @param string $name
     * @return object
     */
    function getParam($name) {
        $db = JFactory::getDbo();
        $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE name = "plg_finder_churchdirectory"');
        $manifest = json_decode($db->loadResult(), true);
        return $manifest[$name];
    }

    /**
     * sets parameter values in the component's row of the extension table
     * @param array $param_array
     */
    function setParams($param_array) {
        if (count($param_array) > 0) {
            // read the existing component value(s)
            $db = JFactory::getDbo();
            $db->setQuery('SELECT params FROM #__extensions WHERE name = "com_churchdirectory"');
            $params = json_decode($db->loadResult(), true);
            // add the new variable(s) to the existing one(s)
            foreach ($param_array as $name => $value) {
                $params[(string) $name] = (string) $value;
            }
            // store the combined new and existing values back as a JSON string
            $paramsString = json_encode($params);
            $db->setQuery('UPDATE #__extensions SET params = ' .
                    $db->quote($paramsString) .
                    ' WHERE name = "com_churchdirectory"');
            $db->query();
        }
    }

}