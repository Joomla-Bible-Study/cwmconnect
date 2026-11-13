<?php

declare(strict_types=1);

namespace Joomla\CMS\Event\User;

/**
 * Minimal stub of Joomla\CMS\Event\User\AfterDeleteEvent for unit tests.
 */
class AfterDeleteEvent implements \Joomla\Event\EventInterface
{
    /**
     * @param  array<string, mixed>  $user  The deleted account.
     */
    public function __construct(
        private readonly array $user,
        private readonly bool $deletingResult,
        private readonly ?string $errorMessage = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getUser(): array
    {
        return $this->user;
    }

    public function getDeletingResult(): bool
    {
        return $this->deletingResult;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage ?? '';
    }
}
