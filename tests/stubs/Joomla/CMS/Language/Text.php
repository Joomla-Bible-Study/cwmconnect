<?php

declare(strict_types=1);

namespace Joomla\CMS\Language;

/**
 * Test-only stub of Joomla\CMS\Language\Text. Returns the key unchanged
 * for _() and a vsprintf'd version for sprintf(). Enough to let
 * component classes that call Text::_ in initializers be instantiated
 * in unit tests without a Joomla install.
 */
class Text
{
    public static function _(string $key): string
    {
        return $key;
    }

    public static function sprintf(string $key, ...$args): string
    {
        return vsprintf($key, $args);
    }

    public static function plural(string $key, int $n, ...$args): string
    {
        return $key;
    }
}
