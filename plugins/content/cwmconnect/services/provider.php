<?php

/**
 * @package    Plg_Content_Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

\defined('_JEXEC') or die;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\DatabaseMemberPairing;
use CWM\Plugin\Content\Cwmconnect\Extension\Cwmconnect;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Cwmconnect::class, function (Container $container) {
                $pairing = new DatabaseMemberPairing($container->get(DatabaseInterface::class));
                $plugin  = new Cwmconnect(
                    (array) PluginHelper::getPlugin('content', 'cwmconnect'),
                    $pairing,
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
