<?php

declare(strict_types=1);

namespace Joomla\Database;

/**
 * Minimal stub of Joomla\Database\ParameterType for unit tests.
 */
enum ParameterType: string
{
    case BOOLEAN = 'bool';
    case INTEGER = 'int';
    case LARGE_OBJECT = 'lob';
    case NULL = 'null';
    case STRING = 'string';
}
