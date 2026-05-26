<?php

declare(strict_types=1);

namespace Joomla\CMS\Application;

/**
 * Minimal stub of Joomla\CMS\Application\ApplicationHelper for unit tests.
 */
class ApplicationHelper
{
    public static function stringURLSafe(string $string, string $language = ''): string
    {
        return strtolower(preg_replace('/[^a-z0-9\-]/i', '-', $string) ?? $string);
    }
}
