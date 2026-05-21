<?php

declare(strict_types=1);

namespace Joomla\Event;

/**
 * Minimal stub of joomla/event's SubscriberInterface for unit tests.
 * Mirrors the production contract: a static map from event name to
 * handler method name (or array of names).
 */
interface SubscriberInterface
{
    /**
     * @return array<string, string|array<int, string>>
     */
    public static function getSubscribedEvents(): array;
}
