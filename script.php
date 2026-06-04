<?php

/**
 * @package    Cwmconnect
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
use Joomla\CMS\Table\Table;

/**
 * Script file for com_cwmconnect.
 *
 * Companion extensions (mod_birthdayanniversary, plg_finder_cwmconnect) ship
 * via the pkg_cwmconnect package wrapper, so the legacy in-script
 * "installation_queue" sub-extension installer is gone. Schema lives in
 * sql/install.mysql.utf8.sql and per-version updates under sql/updates/mysql/.
 *
 * @since  2.0.0
 */
class Com_cwmconnectInstallerScript
{
    /**
     * Minimum PHP version required.
     *
     * @since  2.0.0
     */
    protected string $minimumPhp = '8.4.0';

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
                Text::sprintf('COM_CWMCONNECT requires PHP %s or later. You are running PHP %s.', $this->minimumPhp, PHP_VERSION),
                'error'
            );

            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_CWMCONNECT requires Joomla %s or later.', $this->minimumJoomla),
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
        if ($route === 'install' || $route === 'update') {
            $this->ensureHiddenMenu();
        }

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

    /**
     * Create a hidden menu type with frontend menu items for the component's
     * site views. Admins can alias or link to these from their main menu.
     * The router needs at least one menu item per view to build SEF URLs.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function ensureHiddenMenu(): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        $menuType = 'cwmconnect-hidden';

        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__menu_types'))
            ->where($db->quoteName('menutype') . ' = ' . $db->quote($menuType));

        if ((int) $db->setQuery($query)->loadResult() === 0) {
            $row = (object) [
                'asset_id'    => 0,
                'menutype'    => $menuType,
                'title'       => 'Church Directory (hidden)',
                'description' => 'Auto-created menu items for Church Directory SEF routing. Do not delete — alias these items from your main menu.',
                'client_id'   => 0,
            ];
            $db->insertObject('#__menu_types', $row);
        }

        // v2 front-end views that need a routing-target menu item so the router
        // can build SEF URLs. The legacy member/category/directory/home views
        // were retired; the directory (members) + per-member profile + the
        // self-service myprofile are what remain.
        $views = [
            'members'   => ['title' => 'Church Directory', 'access' => 2],
            'profile'   => ['title' => 'Member Profile',   'access' => 2],
            'myprofile' => ['title' => 'My Profile',       'access' => 2],
        ];

        $componentId = $this->getComponentId($db);

        if ($componentId <= 0) {
            return;
        }

        foreach ($views as $view => $meta) {
            $link  = 'index.php?option=com_cwmconnect&view=' . $view;
            $alias = 'cwmconnect-' . $view;

            // Self-heal upgrades from the legacy layout: an older version
            // created routing items for the now-removed home/member/directory
            // views, and one of them holds this view's canonical alias (e.g.
            // the old "home" item kept `cwmconnect-members`). Joomla's alias
            // uniqueness — which counts trashed rows too — would then block the
            // new item. Retire any mismatched holder of the alias first.
            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('alias') . " = CONCAT(" . $db->quoteName('alias') . ", '-legacy')")
                    ->set($db->quoteName('published') . ' = -2')
                    ->where($db->quoteName('menutype') . ' = ' . $db->quote($menuType))
                    ->where($db->quoteName('client_id') . ' = 0')
                    ->where($db->quoteName('alias') . ' = ' . $db->quote($alias))
                    ->where($db->quoteName('link') . ' <> ' . $db->quote($link)),
            )->execute();

            $exists = $db->createQuery()
                ->select('COUNT(*)')
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('menutype') . ' = ' . $db->quote($menuType))
                ->where($db->quoteName('link') . ' = ' . $db->quote($link))
                ->where($db->quoteName('client_id') . ' = 0');

            if ((int) $db->setQuery($exists)->loadResult() > 0) {
                continue;
            }

            $table = Table::getInstance('Menu');
            $table->menutype     = $menuType;
            $table->title        = $meta['title'];
            $table->alias        = 'cwmconnect-' . $view;
            $table->link         = $link;
            $table->type         = 'component';
            $table->published    = 1;
            $table->parent_id    = 1;
            $table->component_id = $componentId;
            $table->access       = $meta['access'];
            $table->language     = '*';
            $table->client_id    = 0;
            $table->img          = '';
            $table->params       = '{}';
            $table->setLocation(1, 'last-child');
            $table->check();
            $table->store();
        }
    }

    /**
     * @param   object  $db  Database driver.
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    private function getComponentId(object $db): int
    {
        $query = $db->createQuery()
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_cwmconnect'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));

        return (int) $db->setQuery($query)->loadResult();
    }
}
