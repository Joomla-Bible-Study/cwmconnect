<?php

declare(strict_types=1);

namespace Joomla\Event;

/**
 * Minimal stub of Joomla\Event\EventInterface for unit tests.
 *
 * The component's plugin handlers type-hint this interface (the J6-native
 * signature) and read concrete getters via method_exists(), so the stub only
 * needs to exist as a marker the stub event classes can implement to satisfy
 * the type check.
 */
interface EventInterface {}
