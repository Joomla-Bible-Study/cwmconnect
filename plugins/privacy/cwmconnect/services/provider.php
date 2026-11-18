<?php

/**
 * @package    Plg_Privacy_Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

\defined('_JEXEC') or die;

use CWM\Plugin\Privacy\Cwmconnect\Extension\Cwmconnect;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    /**
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function register(Container $container): void
    {
        // CWM Connect needs PHP 8.4, and its classes do not parse on anything older.
        // There, register nothing: Joomla boots a do-nothing plugin in this one's
        // place instead of the parse error taking the site down with it. See
        // admin/services/provider.php.
        if (PHP_VERSION_ID < 80400) {
            return;
        }

        $container->set(
            PluginInterface::class,
            $container->lazy(Cwmconnect::class, function (Container $container) {
                $plugin = new Cwmconnect(
                    (array) PluginHelper::getPlugin('privacy', 'cwmconnect'),
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            })
        );
    }
};
