<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Reconciles untyped request data with typed Table properties.
 *
 * Joomla's {@see \Joomla\CMS\Table\Table::bind()} assigns request values
 * straight onto the table's properties (`$this->$k = $v`). HTML forms post
 * every empty control as `''`, so a blank numeric control — an unset `user`
 * picker, an empty household id — hands `''` to a property declared `?int`
 * and PHP raises "Cannot assign string to property … of type ?int",
 * fatalling the whole save.
 *
 * The types declared on the table are the source of truth here: this reads
 * them by reflection rather than repeating a hand-maintained column list that
 * would drift the moment a column is added.
 *
 * @since  __DEPLOY_VERSION__
 */
trait NormalisesTypedInputTrait
{
    /**
     * Cache of numeric property names → [type, allowsNull], keyed by class.
     *
     * @var    array<string, array<string, array{0: string, 1: bool}>>
     * @since  __DEPLOY_VERSION__
     */
    private static array $numericPropertyCache = [];

    /**
     * Coerce request values to match the declared int/float property types.
     *
     * Empty and non-numeric strings become `null` on a nullable property and
     * `0` otherwise; numeric strings are cast. Anything else is left alone so
     * {@see \Joomla\CMS\Table\Table::check()} still sees the raw value.
     *
     * @param   mixed  $array  Data about to be bound; returned unchanged when
     *                          it is not an array.
     *
     * @return  mixed
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function normaliseTypedInput(mixed $array): mixed
    {
        if (!\is_array($array)) {
            return $array;
        }

        foreach ($this->numericProperties() as $name => [$type, $allowsNull]) {
            if (!\array_key_exists($name, $array) || !\is_string($array[$name])) {
                continue;
            }

            $value = trim($array[$name]);

            if ($value === '' || !is_numeric($value)) {
                $array[$name] = $allowsNull ? null : 0;

                continue;
            }

            $array[$name] = $type === 'int' ? (int) $value : (float) $value;
        }

        return $array;
    }

    /**
     * The table's int/float typed public properties, resolved once per class.
     *
     * @return  array<string, array{0: string, 1: bool}>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function numericProperties(): array
    {
        $class = static::class;

        if (isset(self::$numericPropertyCache[$class])) {
            return self::$numericPropertyCache[$class];
        }

        $properties = [];

        foreach (new \ReflectionClass($class)->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();

            if (!$type instanceof \ReflectionNamedType || !\in_array($type->getName(), ['int', 'float'], true)) {
                continue;
            }

            $properties[$property->getName()] = [$type->getName(), $type->allowsNull()];
        }

        return self::$numericPropertyCache[$class] = $properties;
    }
}
