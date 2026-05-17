<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto' => true
    ])
    // Mirror lint:syntax: only the package's own source trees. Avoids
    // touching node_modules, libraries/ (MAMP-style local Joomla), media
    // build outputs, and anything else that drifts into the repo root.
    ->setFinder(
        (new Finder())
            ->in([
                __DIR__ . '/admin',
                __DIR__ . '/site',
                __DIR__ . '/modules',
                __DIR__ . '/plugins',
                __DIR__ . '/tests',
                __DIR__ . '/build',
            ])
    )
;
