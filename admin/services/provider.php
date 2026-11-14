<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

\defined('_JEXEC') or die;

use CWM\Component\Cwmconnect\Administrator\Extension\CwmconnectComponent;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/*
 * Only what Joomla needs to boot the component. This container is the
 * component's own, which nothing else can reach, so the services the
 * component's code uses (the sync, pairing, feed tokens) are built where
 * they're used rather than registered here.
 */
return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new CategoryFactory('\\CWM\\Component\\Cwmconnect'));
        $container->registerServiceProvider(new MVCFactory('\\CWM\\Component\\Cwmconnect'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\CWM\\Component\\Cwmconnect'));
        $container->registerServiceProvider(new RouterFactory('\\CWM\\Component\\Cwmconnect'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new CwmconnectComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));
                $component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
