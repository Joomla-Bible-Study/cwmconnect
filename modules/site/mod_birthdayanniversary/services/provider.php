<?php

/**
 * @package    Mod_Birthdayanniversary
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\CWM\\Module\\Birthdayanniversary'));
        $container->registerServiceProvider(new HelperFactory('\\CWM\\Module\\Birthdayanniversary\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
