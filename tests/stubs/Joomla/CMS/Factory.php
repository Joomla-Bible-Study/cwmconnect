<?php

declare(strict_types=1);

namespace Joomla\CMS;

/**
 * Minimal stub of Joomla\CMS\Factory for unit tests.
 */
class Factory
{
    public static function getDate(string $date = 'now', mixed $tz = null): object
    {
        return new class ($date) {
            private string $date;

            public function __construct(string $date)
            {
                $this->date = $date;
            }

            public function toSql(): string
            {
                return date('Y-m-d H:i:s');
            }

            public function format(string $format): string
            {
                return date($format);
            }
        };
    }

    public static function getApplication(): object
    {
        return new class {
            public function getIdentity(): ?object
            {
                return (object) ['id' => 0];
            }

            public function getInput(): object
            {
                return new class {
                    public function get(string $name, mixed $default = null): mixed
                    {
                        return $default;
                    }
                };
            }
        };
    }
}
