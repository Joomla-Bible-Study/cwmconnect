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
        // CWM Connect needs PHP 8.4, and its classes do not parse on anything older.
        // There, register nothing: Joomla falls back to a legacy module that renders
        // nothing instead of the parse error taking the page down with it. See
        // admin/services/provider.php.
        if (PHP_VERSION_ID < 80400) {
            return;
        }

        $container->registerServiceProvider(new ModuleDispatcherFactory('\\CWM\\Module\\Birthdayanniversary'));
        $container->registerServiceProvider(new HelperFactory('\\CWM\\Module\\Birthdayanniversary\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
