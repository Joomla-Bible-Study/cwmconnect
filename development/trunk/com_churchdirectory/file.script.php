<?php

/**
 * Main install Script
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;

/**
 * Class for install Script
 *
 * @package             ChurchDirectory.Admin
 * @since 1.7.0
 */
class com_churchdirectoryInstallerScript {

    /** @var string The component's name */
    protected $_churchdirectory_extension = 'com_akeeba';

    /**
     * The list of extra modules and plugins to install
     * @author Nicholas K. Dionysopoulos
     * @var array
     */
    private $installation_queue = array(
        // modules => { (folder) => { (module) => { (position), (published) } }* }*
        'modules' => array(
            'admin' => array(
            ),
            'site' => array(
                'birthdayanniversary' => 0,
            )
        ),
        // plugins => { (folder) => { (element) => (published) }* }*
        'plugins' => array(
            'finder' => array(
                'churchdirectory_finder' => 1,
            ),
            'search' => array(
                'churchdirectory_search' => 0,
            ),
        )
    );

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

        // Bugfix for "Can not build admin menus"
        if (in_array($type, array('install', 'discover_install'))) {
            $this->_bugfixDBFunctionReturnedNoError();
        } else {
            $this->_bugfixCantBuildAdminMenus();
        }

        // this component does not work with Joomla releases prior to 1.7
        // abort if the current Joomla release is older
        $jversion = new JVersion();

        // Extract the version number from the manifest. This will overwrite the 1.0 value set above
        $this->release = $parent->get("manifest")->version;

        // Find mimimum required joomla version
        $this->minimum_joomla_release = $parent->get("manifest")->attributes()->version;

        if (version_compare($jversion->getShortVersion(), $this->minimum_joomla_release, 'lt')) {
            Jerror::raiseWarning(null, 'Cannot install com_churchdirectory in a Joomla release prior to ' . $this->minimum_joomla_release);
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
        } else {
            $rel = $this->release;
        }

        echo '<p>' . JText::_('COM_CHURCHDIRECTORY_PREFLIGHT_' . $type . ' ' . $rel) . '</p>';
    }

    /**
     * install runs after the database scripts are executed.
     * If the extension is new, the install method is run.
     * If install returns false, Joomla will abort the install and undo everything already done.
     *
     * @param string $parent is the class calling this method.
     */
    function install($parent) {
        $installation_queue = array(
            // modules => { (folder) => { (module) => { (position), (published) } }* }*
            'modules' => array(
                'admin' => array(
                ),
                'site' => array(
                    'birthdayanniversary' => 0,
                )
            ),
            // plugins => { (folder) => { (element) => (published) }* }*
            'plugins' => array(
                'finder' => array(
                    'churchdirectory_finder' => 1,
                ),
                'search' => array(
                    'churchdirectory_search' => 0,
                ),
                'system' => array(
                    'jbsbackup' => 0,
                    'jbspodcast' => 0,
                )
            )
        );
        // -- General settings

        jimport('joomla.installer.installer');
        $db = JFactory::getDBO();
        $status = new JObject();
        $status->modules = array();
        $status->plugins = array();

        // Modules installation
        if (count($installation_queue['modules'])) {
            foreach ($installation_queue['modules'] as $folder => $modules) {
                if (count($modules))
                    foreach ($modules as $module => $modulePreferences) {
                        // Install the module
                        if (empty($folder))
                            $folder = 'site';
                        $path = "$src/modules/$folder/$module";
                        if (!is_dir($path)) {
                            $path = "$src/modules/$folder/mod_$module";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/modules/$module";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/modules/mod_$module";
                        }
                        if (!is_dir($path))
                            continue;
                        // Was the module already installed?
                        $sql = 'SELECT COUNT(*) FROM #__modules WHERE `module`=' . $db->Quote('mod_' . $module);
                        $db->setQuery($sql);
                        $count = $db->loadResult();
                        $installer = new JInstaller;
                        $result = $installer->install($path);
                        $status->modules[] = array('name' => 'mod_' . $module, 'client' => $folder, 'result' => $result);
                    }
            }
        }
        // Plugins installation
        if (count($installation_queue['plugins'])) {
            foreach ($installation_queue['plugins'] as $folder => $plugins) {
                if (count($plugins))
                    foreach ($plugins as $plugin => $published) {
                        $path = "$src/plugins/$folder/$plugin";
                        if (!is_dir($path)) {
                            $path = "$src/plugins/$folder/plg_$plugin";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/plugins/$plugin";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/plugins/plg_$plugin";
                        }
                        if (!is_dir($path))
                            continue;
                        // Was the module already installed?
                        $query = "SELECT COUNT(*) FROM  #__extensions WHERE element=" . $db->Quote($plugin) . " AND folder=" . $db->Quote($folder);
                        $db->setQuery($query);
                        $result = $db->loadResult();

                        $installer = new JInstaller;
                        $result = $installer->install($path);
                        $status->plugins[] = array('name' => 'plg_' . $plugin, 'group' => $folder, 'result' => $result);

                        if ($published && !$count) {
                            $query = "UPDATE #__extensions SET enabled=1 WHERE element=" . $db->Quote($plugin) . " AND folder=" . $db->Quote($folder);
                            $db->setQuery($query);
                            $db->query();
                        }
                    }
            }
        }


        echo '<p>' . JText::_('COM_CHURCHDIRECTORY_INSTALL to ' . $this->release) . '</p>';
        // You can have the backend jump directly to the newly installed component configuration page
        $parent->getParent()->setRedirectURL('index.php?option=com_churchdirectory');
    }

    /**
     * update runs after the database scripts are executed.
     * If the extension exists, then the update method is run.
     * If this returns false, Joomla will abort the update and undo everything already done.
     *
     * @param string $parent is the class calling this method.
     */
    function update($parent) {
        echo '<p>' . JText::_('COM_CHURCHDIRECTORY_UPDATE_ to ' . $this->release) . '</p>';
    }

    /**
     * postflight is run after the extension is registered in the database.
     * @param type $type is the type of change (install, update or discover_install, not uninstall).
     * @param type $parent is the class calling this method.
     */
    function postflight($type, $parent) {
        // Install subextensions
        $status = $this->_installSubextensions($parent);

        // set initial values for component parameters
        $params['my_param0'] = 'Component version ' . $this->release;
        $params['my_param1'] = 'Another value';
        $params['my_param2'] = 'Still yet another value';
        $this->setParams($params);

        echo '<p>' . JText::_('COM_CHURCHDIRECTORY_POSTFLIGHT ' . $type . ' to ' . $this->release) . '</p>';
    }

    /**
     * uninstall runs before any other action is taken (file removal or database processing)
     * @param string $parent
     */
    function uninstall($parent) {
        echo '<p>' . JText::_('COM_CHURCHDIRECTORY_UNINSTALL ' . $this->release) . '</p>';
    }

    /**
     * get a variable from the manifest file (actually, from the manifest cache).
     * @param string $name
     * @return object
     */
    function getParam($name) {
        $db = JFactory::getDbo();
        $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE name = "com_churchdirectory"');
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

    /**
     * Joomla! 1.6+ bugfix for "Can not build admin menus"
     */
    private function _bugfixCantBuildAdminMenus() {
        $db = JFactory::getDbo();

        // If there are multiple #__extensions record, keep one of them
        $query = $db->getQuery(true);
        $query->select('extension_id')
                ->from('#__extensions')
                ->where($db->qn('element') . ' = ' . $db->q($this->_akeeba_extension));
        $db->setQuery($query);
        $ids = $db->loadColumn();
        if (count($ids) > 1) {
            asort($ids);
            $extension_id = array_shift($ids); // Keep the oldest id

            foreach ($ids as $id) {
                $query = $db->getQuery(true);
                $query->delete('#__extensions')
                        ->where($db->qn('extension_id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->query();
            }
        }

        // @todo
        // If there are multiple assets records, delete all except the oldest one
        $query = $db->getQuery(true);
        $query->select('id')
                ->from('#__assets')
                ->where($db->qn('name') . ' = ' . $db->q($this->_akeeba_extension));
        $db->setQuery($query);
        $ids = $db->loadObjectList();
        if (count($ids) > 1) {
            asort($ids);
            $asset_id = array_shift($ids); // Keep the oldest id

            foreach ($ids as $id) {
                $query = $db->getQuery(true);
                $query->delete('#__assets')
                        ->where($db->qn('id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->query();
            }
        }

        // Remove #__menu records for good measure!
        $query = $db->getQuery(true);
        $query->select('id')
                ->from('#__menu')
                ->where($db->qn('type') . ' = ' . $db->q('component'))
                ->where($db->qn('menutype') . ' = ' . $db->q('main'))
                ->where($db->qn('link') . ' LIKE ' . $db->q('index.php?option=' . $this->_akeeba_extension));
        $db->setQuery($query);
        $ids1 = $db->loadColumn();
        if (empty($ids1))
            $ids1 = array();
        $query = $db->getQuery(true);
        $query->select('id')
                ->from('#__menu')
                ->where($db->qn('type') . ' = ' . $db->q('component'))
                ->where($db->qn('menutype') . ' = ' . $db->q('main'))
                ->where($db->qn('link') . ' LIKE ' . $db->q('index.php?option=' . $this->_akeeba_extension . '&%'));
        $db->setQuery($query);
        $ids2 = $db->loadColumn();
        if (empty($ids2))
            $ids2 = array();
        $ids = array_merge($ids1, $ids2);
        if (!empty($ids))
            foreach ($ids as $id) {
                $query = $db->getQuery(true);
                $query->delete('#__menu')
                        ->where($db->qn('id') . ' = ' . $db->q($id));
                $db->setQuery($query);
                $db->query();
            }
    }

    /**
     * Installs subextensions (modules, plugins) bundled with the main extension
     *
     * @param JInstaller $parent
     * @return JObject The subextension installation status
     */
    private function _installSubextensions($parent) {
        $src = $parent->getParent()->getPath('source');

        $db = JFactory::getDbo();

        $status = new JObject();
        $status->modules = array();
        $status->plugins = array();

        // Modules installation
        if (count($this->installation_queue['modules'])) {
            foreach ($this->installation_queue['modules'] as $folder => $modules) {
                if (count($modules))
                    foreach ($modules as $module => $modulePreferences) {
                        // Install the module
                        if (empty($folder))
                            $folder = 'site';
                        $path = "$src/modules/$folder/$module";
                        if (!is_dir($path)) {
                            $path = "$src/modules/$folder/mod_$module";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/modules/$module";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/modules/mod_$module";
                        }
                        if (!is_dir($path))
                            continue;
                        // Was the module already installed?
                        $sql = $db->getQuery(true)
                                ->select('COUNT(*)')
                                ->from('#__modules')
                                ->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
                        $db->setQuery($sql);
                        $count = $db->loadResult();
                        $installer = new JInstaller;
                        $result = $installer->install($path);
                        $status->modules[] = array(
                            'name' => 'mod_' . $module,
                            'client' => $folder,
                            'result' => $result
                        );
                        // Modify where it's published and its published state
                        if (!$count) {
                            // A. Position and state
                            list($modulePosition, $modulePublished) = $modulePreferences;
                            if ($modulePosition == 'cpanel') {
                                $modulePosition = 'icon';
                            }
                            $sql = $db->getQuery(true)
                                    ->update($db->qn('#__modules'))
                                    ->set($db->qn('position') . ' = ' . $db->q($modulePosition))
                                    ->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
                            if ($modulePublished) {
                                $sql->set($db->qn('published') . ' = ' . $db->q('1'));
                            }
                            $db->setQuery($sql);
                            $db->query();

                            // B. Change the ordering of back-end modules to 1 + max ordering
                            if ($folder == 'admin') {
                                $query = $db->getQuery(true);
                                $query->select('MAX(' . $db->qn('ordering') . ')')
                                        ->from($db->qn('#__modules'))
                                        ->where($db->qn('position') . '=' . $db->q($modulePosition));
                                $db->setQuery($query);
                                $position = $db->loadResult();
                                $position++;

                                $query = $db->getQuery(true);
                                $query->update($db->qn('#__modules'))
                                        ->set($db->qn('ordering') . ' = ' . $db->q($position))
                                        ->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
                                $db->setQuery($query);
                                $db->query();
                            }

                            // C. Link to all pages
                            $query = $db->getQuery(true);
                            $query->select('id')->from($db->qn('#__modules'))
                                    ->where($db->qn('module') . ' = ' . $db->q('mod_' . $module));
                            $db->setQuery($query);
                            $moduleid = $db->loadResult();

                            $query = $db->getQuery(true);
                            $query->select('*')->from($db->qn('#__modules_menu'))
                                    ->where($db->qn('moduleid') . ' = ' . $db->q($moduleid));
                            $db->setQuery($query);
                            $assignments = $db->loadObjectList();
                            $isAssigned = !empty($assignments);
                            if (!$isAssigned) {
                                $o = (object) array(
                                            'moduleid' => $moduleid,
                                            'menuid' => 0
                                );
                                $db->insertObject('#__modules_menu', $o);
                            }
                        }
                    }
            }
        }

        // Plugins installation
        if (count($this->installation_queue['plugins'])) {
            foreach ($this->installation_queue['plugins'] as $folder => $plugins) {
                if (count($plugins))
                    foreach ($plugins as $plugin => $published) {
                        $path = "$src/plugins/$folder/$plugin";
                        if (!is_dir($path)) {
                            $path = "$src/plugins/$folder/plg_$plugin";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/plugins/$plugin";
                        }
                        if (!is_dir($path)) {
                            $path = "$src/plugins/plg_$plugin";
                        }
                        if (!is_dir($path))
                            continue;

                        // Was the plugin already installed?
                        $query = $db->getQuery(true)
                                ->select('COUNT(*)')
                                ->from($db->qn('#__extensions'))
                                ->where($db->qn('element') . ' = ' . $db->q($plugin))
                                ->where($db->qn('folder') . ' = ' . $db->q($folder));
                        $db->setQuery($query);
                        $count = $db->loadResult();

                        $installer = new JInstaller;
                        $result = $installer->install($path);

                        $status->plugins[] = array('name' => 'plg_' . $plugin, 'group' => $folder, 'result' => $result);

                        if ($published && !$count) {
                            $query = $db->getQuery(true)
                                    ->update($db->qn('#__extensions'))
                                    ->set($db->qn('enabled') . ' = ' . $db->q('1'))
                                    ->where($db->qn('element') . ' = ' . $db->q($plugin))
                                    ->where($db->qn('folder') . ' = ' . $db->q($folder));
                            $db->setQuery($query);
                            $db->query();
                        }
                    }
            }
        }

        return $status;
    }

    /**
     * Uninstalls subextensions (modules, plugins) bundled with the main extension
     *
     * @param JInstaller $parent
     * @return JObject The subextension uninstallation status
     */
    private function _uninstallSubextensions($parent) {
        jimport('joomla.installer.installer');

        $db = & JFactory::getDBO();

        $status = new JObject();
        $status->modules = array();
        $status->plugins = array();

        $src = $parent->getParent()->getPath('source');

        // Modules uninstallation
        if (count($this->installation_queue['modules'])) {
            foreach ($this->installation_queue['modules'] as $folder => $modules) {
                if (count($modules))
                    foreach ($modules as $module => $modulePreferences) {
                        // Find the module ID
                        $sql = $db->getQuery(true)
                                ->select($db->qn('extension_id'))
                                ->from($db->qn('#__extensions'))
                                ->where($db->qn('element') . ' = ' . $db->q('mod_' . $module))
                                ->where($db->qn('type') . ' = ' . $db->q('module'));
                        $db->setQuery($sql);
                        $id = $db->loadResult();
                        // Uninstall the module
                        if ($id) {
                            $installer = new JInstaller;
                            $result = $installer->uninstall('module', $id, 1);
                            $status->modules[] = array(
                                'name' => 'mod_' . $module,
                                'client' => $folder,
                                'result' => $result
                            );
                        }
                    }
            }
        }

        // Plugins uninstallation
        if (count($this->installation_queue['plugins'])) {
            foreach ($this->installation_queue['plugins'] as $folder => $plugins) {
                if (count($plugins))
                    foreach ($plugins as $plugin => $published) {
                        $sql = $db->getQuery(true)
                                ->select($db->qn('extension_id'))
                                ->from($db->qn('#__extensions'))
                                ->where($db->qn('type') . ' = ' . $db->q('plugin'))
                                ->where($db->qn('element') . ' = ' . $db->q($plugin))
                                ->where($db->qn('folder') . ' = ' . $db->q($folder));
                        $db->setQuery($sql);

                        $id = $db->loadResult();
                        if ($id) {
                            $installer = new JInstaller;
                            $result = $installer->uninstall('plugin', $id, 1);
                            $status->plugins[] = array(
                                'name' => 'plg_' . $plugin,
                                'group' => $folder,
                                'result' => $result
                            );
                        }
                    }
            }
        }

        return $status;
    }

}