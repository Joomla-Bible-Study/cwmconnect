<?php

/**
 * Package installer script for pkg_cwmconnect.
 *
 * Switches on the bundled plugins a working directory needs, and only on a fresh
 * install: an update never touches them, so a plugin an admin has since switched
 * off stays off.
 *
 * @package    Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      __DEPLOY_VERSION__
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Returns an anonymous class implementing InstallerScriptInterface.
 *
 * Joomla 5+ expects the script file to return an InstallerScriptInterface
 * instance directly (not define a named class).
 *
 * @since  __DEPLOY_VERSION__
 */
return new class implements InstallerScriptInterface {
    /**
     * Plugin groups whose cwmconnect plugin a fresh install switches on.
     *
     * Smart Search (finder) is left off: listing members in site search is the
     * administrator's choice, and the index is built once when they make it.
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    private const array ENABLE_ON_INSTALL = ['content', 'privacy', 'task', 'user'];

    /**
     * Runs on install.
     *
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs on update.
     *
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs on uninstall.
     *
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs before install/update/uninstall.
     *
     * @param   string            $type     Install type
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs after install/update/uninstall, once every child extension is in place.
     *
     * @param   string            $type     Install type
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'install') {
            $this->enablePlugins();
        }

        return true;
    }

    /**
     * Switch on the plugins in ENABLE_ON_INSTALL and tell the admin what was, and was
     * not, switched on. A failure only warns: the install itself has succeeded, and
     * the plugins can always be switched on by hand.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function enablePlugins(): void
    {
        $app = Factory::getApplication();

        try {
            $db      = Factory::getContainer()->get(DatabaseInterface::class);
            $type    = 'plugin';
            $element = 'cwmconnect';
            $query   = $db->createQuery()
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = :type')
                ->where($db->quoteName('element') . ' = :element')
                ->whereIn($db->quoteName('folder'), self::ENABLE_ON_INSTALL, ParameterType::STRING)
                ->bind(':type', $type)
                ->bind(':element', $element);

            $db->setQuery($query)->execute();
        } catch (\Throwable $e) {
            Log::add(
                'pkg_cwmconnect: could not switch on the bundled plugins (' . $e->getMessage() . ').',
                Log::WARNING,
                'com_cwmconnect'
            );
            $app->enqueueMessage(
                'CWM Connect could not switch on its plugins. Switch on the Church Directory pairing, privacy'
                . ' and sync plugins in System → Manage → Plugins.',
                'warning'
            );

            return;
        }

        $app->enqueueMessage(
            'CWM Connect switched on its pairing, privacy and scheduled-sync plugins. Smart Search - Church'
            . ' Directory is left off: to list members in site search, switch it on and build the index once'
            . ' under Components → Smart Search.',
            'message'
        );
    }
};
