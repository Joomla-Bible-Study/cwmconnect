<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for the cwmconnect test suite.
 *
 * Loads Composer's PSR-4 autoload (covers the component, module, and
 * plugin namespaces) and registers a small fall-through autoloader for
 * the Joomla\CMS\* / Joomla\Event\* stubs under tests/stubs/. The stubs
 * give the component classes just enough Joomla to be unit-testable
 * outside a real Joomla install. Tests that need richer state should
 * mock what they need locally rather than expanding the stub tree.
 */

require_once __DIR__ . '/../libraries/vendor/autoload.php';

if (!\defined('_JEXEC')) {
    \define('_JEXEC', 1);
}

if (!\defined('JPATH_LIBRARIES')) {
    \define('JPATH_LIBRARIES', __DIR__ . '/../libraries');
}

if (!\defined('JPATH_ADMINISTRATOR')) {
    \define('JPATH_ADMINISTRATOR', __DIR__ . '/stubs/administrator');
}

if (!\defined('JPATH_SITE')) {
    \define('JPATH_SITE', __DIR__ . '/stubs/site');
}

/*
 * Stub autoloader — only loads a class if (a) no other autoloader has
 * already provided it, and (b) we have a matching file under
 * tests/stubs/. Keeps stubs invisible when the real Joomla framework
 * is on the include path.
 */
spl_autoload_register(static function (string $class): void {
    $path = __DIR__ . '/stubs/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});
