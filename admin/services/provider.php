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
        // CWM Connect needs PHP 8.4, the floor its installer and update feed hold too.
        // Below it, its classes may not even parse, and a site that ends up there,
        // say after its host changes PHP, must keep a working Joomla. So none of
        // CWM Connect's code loads: administrators get a banner, and the component
        // answers with it instead of running. Keep 8.4-only syntax out of this
        // file, or the guard itself cannot run.
        if (PHP_VERSION_ID < 80400) {
            $this->standDown($container);

            return;
        }

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

    /**
     * Stand CWM Connect down on a PHP below its floor, without breaking the site.
     *
     * Registers Joomla's own LegacyComponent, which answers every service a booted
     * component is asked for without loading any of CWM Connect's classes, and replaces
     * only its dispatcher. In the backend that dispatcher returns to the
     * dashboard, where the banner says why; on the site and the API it answers 503
     * with an empty body. Nothing is thrown, so there is no error page.
     *
     * @param   Container  $container  The component's container.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function standDown(Container $container): void
    {
        $this->postPhpBanner();

        $container->set(
            \Joomla\CMS\Extension\ComponentInterface::class,
            static fn() => new class ('com_cwmconnect') extends \Joomla\CMS\Extension\LegacyComponent {
                public function getDispatcher(
                    \Joomla\CMS\Application\CMSApplicationInterface $application
                ): \Joomla\CMS\Dispatcher\DispatcherInterface {
                    return new class ($application) implements \Joomla\CMS\Dispatcher\DispatcherInterface {
                        public function __construct(
                            private \Joomla\CMS\Application\CMSApplicationInterface $application
                        ) {}

                        public function dispatch(): void
                        {
                            if ($this->application->isClient('administrator')) {
                                $this->application->redirect('index.php');
                            }

                            $this->application->setHeader('status', '503', true);
                        }
                    };
                }
            }
        );
    }

    /**
     * Tell administrators that CWM Connect is switched off, and why.
     *
     * Backend only: a visitor can do nothing about the server's PHP. Posted once
     * per request, by whichever of CWM Connect's extensions boots first.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function postPhpBanner(): void
    {
        try {
            $app = \Joomla\CMS\Factory::getApplication();
        } catch (\Throwable) {
            // No application yet, e.g. the CLI before it has booted.
            return;
        }

        if (!$app->isClient('administrator')) {
            return;
        }

        $message = \sprintf(
            '%1$s needs PHP 8.4.0 or later. This site runs PHP %2$s, so %1$s is switched off until PHP is upgraded.',
            'CWM Connect',
            PHP_VERSION
        );

        foreach ($app->getMessageQueue() as $queued) {
            if (($queued['message'] ?? null) === $message) {
                return;
            }
        }

        $app->enqueueMessage($message, 'error');
    }
};
