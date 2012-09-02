<?php
/**
 * Main install Script
 * This Script is bassed off AkeebaBackup component.
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
    protected $_churchdirectory_extension = 'com_churchdirectory';

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
        }
    }

    /**
     * update runs after the database scripts are executed.
     * If the extension exists, then the update method is run.
     * If this returns false, Joomla will abort the update and undo everything already done.
     *
     * @param string $parent is the class calling this method.
     */
    function update($parent) {

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

        // Show the post-installation page
        $this->_renderPostInstallation($status, $fofStatus, $straperStatus, $parent);
    }

    /**
     * uninstall runs before any other action is taken (file removal or database processing)
     * @param string $parent
     */
    function uninstall($parent) {
        // Uninstall subextensions
        $status = $this->_uninstallSubextensions($parent);

        // Show the post-uninstallation page
        $this->_renderPostUninstallation($status, $parent);
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
            $db->execute();
        }
    }

    /**
     * Renders the post-installation message
     * @since 1.7.4
     * @todo need to add verion check system.
     */
    private function _renderPostInstallation($status, $parent) {
        ?>

        <?php $rows = 1; ?>
        <img src="../media/com_churchdirectory/images/icons/icon-48-churchdirectory.png" width="48" height="48" alt="ChurchDirectory" align="right" />

        <h2>Welcome to Church Directory System</h2>

        <table class="adminlist">
            <thead>
                <tr>
                    <th class="title" colspan="2">Extension</th>
                    <th width="30%"><?php echo JTEXT::_('COM_CHURCHDIRECTORY_STATUS'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="row0">
                    <td class="key" colspan="2"><?php echo JTEXT::_('COM_CHURCHDIRECTORY_COMPONENT'); ?></td>
                    <td><strong style="color: green"><?php echo JTEXT::_('COM_CHURCHDIRECTORY_INSTALLED'); ?></strong></td>
                </tr>
        <?php if (count($status->modules)) : ?>
                    <tr>
                        <th>Module</th>
                        <th>Client</th>
                        <th></th>
                    </tr>
            <?php foreach ($status->modules as $module) : ?>
                        <tr class="row<?php echo ($rows++ % 2); ?>">
                            <td class="key"><?php echo $module['name']; ?></td>
                            <td class="key"><?php echo ucfirst($module['client']); ?></td>
                            <td><strong style="color: <?php echo ($module['result']) ? "green" : "red" ?>"><?php echo ($module['result']) ? JTEXT::_('COM_CHURCHDIRECTORY_INSTALLED') : JTEXT::_('COM_CHURCHDIRECTORY_NOT_INSTALLED'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
        <?php if (count($status->plugins)) : ?>
                    <tr>
                        <th>Plugin</th>
                        <th>Group</th>
                        <th></th>
                    </tr>
            <?php foreach ($status->plugins as $plugin) : ?>
                        <tr class="row<?php echo ($rows++ % 2); ?>">
                            <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                            <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
                            <td><strong style="color: <?php echo ($plugin['result']) ? "green" : "red" ?>"><?php echo ($plugin['result']) ? JTEXT::_('COM_CHURCHDIRECTORY_INSTALLED') : JTEXT::_('COM_CHURCHDIRECTORY_NOT_INSTALLED'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
        <?php endif; ?>
            </tbody>
        </table>

        <fieldset>
            <p></p>
        </fieldset>
        <?php
    }

    private function _renderPostUninstallation($status, $parent) {
        ?>
        <?php $rows = 0; ?>
        <h2><?php echo JText::_('COM_CHURCHDIRECTORY_UNINSTALL'); ?></h2>
        <table class="adminlist">
            <thead>
                <tr>
                    <th class="title" colspan="2"><?php echo JText::_('COM_CHURCHDIRECTORY_EXTENSION'); ?></th>
                    <th width="30%"><?php echo JText::_('COM_CHURCHDIRECTORY_STATUS'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="row0">
                    <td class="key" colspan="2"><?php echo JText::_('COM_CHURCHDIRECTORY'); ?></td>
                    <td><strong style="color: green"><?php echo JText::_('COM_CHURCHDIRECTORY_REMOVED'); ?></strong></td>
                </tr>
        <?php if (count($status->modules)) : ?>
                    <tr>
                        <th><?php echo JText::_('COM_CHURCHDIRECTORY_MODULE'); ?></th>
                        <th><?php echo JText::_('COM_CHURCHDIRECTORY_CLIENT'); ?></th>
                        <th></th>
                    </tr>
            <?php foreach ($status->modules as $module) : ?>
                        <tr class="row<?php echo (++$rows % 2); ?>">
                            <td class="key"><?php echo $module['name']; ?></td>
                            <td class="key"><?php echo ucfirst($module['client']); ?></td>
                            <td><strong style="color: <?php echo ($module['result']) ? "green" : "red" ?>"><?php echo ($module['result']) ? JText::_('COM_CHURCHDIRECTORY_REMOVED') : JText::_('COM_CHURCHDIRECTORY_NOT_REMOVED'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
        <?php if (count($status->plugins)) : ?>
                    <tr>
                        <th><?php echo JText::_('Plugin'); ?></th>
                        <th><?php echo JText::_('Group'); ?></th>
                        <th></th>
                    </tr>
            <?php foreach ($status->plugins as $plugin) : ?>
                        <tr class="row<?php echo (++$rows % 2); ?>">
                            <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                            <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
                            <td><strong style="color: <?php echo ($plugin['result']) ? "green" : "red" ?>"><?php echo ($plugin['result']) ? JText::_('COM_CHURCHDIRECTORY_REMOVED') : JText::_('COM_CHURCHDIRECTORY_NOT_REMOVED'); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
        <?php endif; ?>
            </tbody>
        </table>
        <?php
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
                            $db->execute();

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
                                $db->execute();
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
                            $db->execute();
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

        $db = JFactory::getDBO();

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