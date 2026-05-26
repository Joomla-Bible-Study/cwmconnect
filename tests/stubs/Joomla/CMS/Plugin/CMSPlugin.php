<?php

declare(strict_types=1);

namespace Joomla\CMS\Plugin;

/**
 * Minimal stub of joomla/cms's CMSPlugin base class for unit tests.
 *
 * Production CMSPlugin pulls in DispatcherAwareInterface, ApplicationAware,
 * language loading, etc. Our plugin only relies on being constructible
 * and on declaring `autoloadLanguage`; tests don't exercise the wider
 * surface.
 */
abstract class CMSPlugin
{
    /** @var bool */
    protected $autoloadLanguage = false;

    /**
     * @param  mixed                 $subject  Production passes a Dispatcher; tests pass null.
     * @param  array<string, mixed>  $config   The plugin's config row.
     */
    public function __construct($subject = null, array $config = [])
    {
        // No-op for test purposes. Production stores the dispatcher
        // and reads `params` from $config.
    }

    /**
     * Joomla calls setApplication on plugins resolved through the DI
     * container. We just need the method to exist so the service
     * provider can call it without blowing up under tests.
     *
     * @param  mixed  $app
     */
    public function setApplication($app): void
    {
        // No-op for test purposes.
    }
}
