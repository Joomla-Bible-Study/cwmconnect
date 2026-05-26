<?php

declare(strict_types=1);

namespace Joomla\String;

/**
 * Minimal stub of Joomla\String\StringHelper for unit tests.
 */
class StringHelper
{
    public static function str_ireplace(
        string|array $search,
        string|array $replace,
        string|array $subject
    ): string|array {
        return str_ireplace($search, $replace, $subject);
    }

    public static function increment(string $string, string $style = 'default', int $n = 0): string
    {
        return $string . ($style === 'dash' ? '-2' : ' (2)');
    }
}
