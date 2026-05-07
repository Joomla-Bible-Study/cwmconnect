<?php

/**
 * @package    Churchdirectory
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;

/**
 * Script file for com_churchdirectory.
 *
 * Companion extensions (mod_birthdayanniversary, plg_finder_churchdirectory) ship
 * via the pkg_cwmconnect package wrapper, so the legacy in-script
 * "installation_queue" sub-extension installer is gone. Schema lives in
 * sql/install.mysql.utf8.sql and per-version updates under sql/updates/mysql/.
 *
 * @since  2.0.0
 */
class Com_churchdirectoryInstallerScript
{
    /**
     * Minimum PHP version required.
     *
     * @since  2.0.0
     */
    protected string $minimumPhp = '8.3.0';

    /**
     * Minimum Joomla version required.
     *
     * @since  2.0.0
     */
    protected string $minimumJoomla = '5.0.0';

    /**
     * Method to install the extension.
     *
     * @since  2.0.0
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Method to uninstall the extension.
     *
     * @since  2.0.0
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Method to update the extension.
     *
     * @since  2.0.0
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Function called before extension installation/update/removal.
     *
     * @param  string  $route  Which action is happening (install|uninstall|update)
     *
     * @since  2.0.0
     */
    public function preflight(string $route, InstallerAdapter $adapter): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_CHURCHDIRECTORY requires PHP %s or later. You are running PHP %s.', $this->minimumPhp, PHP_VERSION),
                'error'
            );

            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_CHURCHDIRECTORY requires Joomla %s or later.', $this->minimumJoomla),
                'error'
            );

            return false;
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal.
     *
     * @param  string  $route  Which action is happening (install|uninstall|update)
     *
     * @since  2.0.0
     */
    public function postflight(string $route, InstallerAdapter $adapter): bool
    {
        if ($route === 'install') {
            Factory::getApplication()->enqueueMessage(
                'CWM Connect 2.0.0 has been installed successfully.',
                'message'
            );
        }

        if ($route === 'update') {
            Factory::getApplication()->enqueueMessage(
                'CWM Connect has been updated to version 2.0.0.',
                'message'
            );
        }

        return true;
    }
}
