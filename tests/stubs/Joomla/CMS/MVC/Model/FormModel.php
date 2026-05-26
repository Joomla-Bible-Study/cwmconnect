<?php

declare(strict_types=1);

namespace Joomla\CMS\MVC\Model;

/**
 * Minimal stub of Joomla\CMS\MVC\Model\FormModel for unit tests.
 *
 * Production FormModel pulls in the full Joomla MVC stack (state, database,
 * event dispatcher, form loader). Our portal model only needs the class to
 * exist at autoload time so PHPUnit can resolve the model namespace and
 * call its static helpers. Tests that touch instance state (getForm, save,
 * loadItemByUserId) are integration-only and live outside `tests/unit/`.
 */
abstract class FormModel
{
    /** @var string */
    protected $context = '';

    public function __construct(array $config = [])
    {
        // No-op for test purposes.
    }

    protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = null)
    {
        return null;
    }

    protected function getDatabase()
    {
        return null;
    }

    protected function getState($property = null, $default = null)
    {
        return $default;
    }

    protected function setState($property, $value = null): void
    {
        // No-op for test purposes.
    }

    protected function setError($error): void
    {
        // No-op for test purposes.
    }

    public function getError(): string
    {
        return '';
    }
}
