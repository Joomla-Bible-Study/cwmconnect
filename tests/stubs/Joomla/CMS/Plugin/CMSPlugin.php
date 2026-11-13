<?php

declare(strict_types=1);

namespace Joomla\CMS\Plugin;

/**
 * Minimal stub of joomla/cms's CMSPlugin base class for unit tests.
 *
 * Production CMSPlugin pulls in DispatcherAwareInterface, ApplicationAware,
 * language loading, etc. Our plugins rely on being constructible, on
 * declaring `autoloadLanguage`, and on reading back the application they
 * were given; tests don't exercise the wider surface.
 */
abstract class CMSPlugin
{
    /** @var bool */
    protected $autoloadLanguage = false;

    /** @var mixed */
    private $application = null;

    /**
     * J6.1+ single-arg constructor. The legacy 2-arg form (dispatcher,
     * config) is deprecated in J6.1 and removed in J7.
     *
     * @param  array<string, mixed>  $config  The plugin's config row.
     */
    public function __construct(array $config = [])
    {
        // No-op for test purposes.
    }

    /**
     * Joomla calls setApplication on plugins resolved through the DI
     * container. Kept so a test can hand a plugin a stand-in application
     * (anything with isClient()) and read it back via getApplication().
     *
     * @param  mixed  $app
     */
    public function setApplication($app): void
    {
        $this->application = $app;
    }

    /**
     * Null until setApplication() is called, as in production.
     *
     * @return  mixed
     */
    protected function getApplication()
    {
        return $this->application;
    }
}
