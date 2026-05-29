<?php

declare(strict_types=1);

namespace Joomla\CMS\Event\User;

/**
 * Minimal stub of Joomla\CMS\Event\User\AfterSaveEvent for unit tests.
 *
 * Mirrors just the getters our plugin handler reads. Production class
 * derives from a Joomla\Event\Event chain we don't need under test; the
 * EventInterface marker satisfies the handler's parameter type.
 */
class AfterSaveEvent implements \Joomla\Event\EventInterface
{
    /**
     * @param  array<string, mixed>  $user
     */
    public function __construct(
        private readonly array $user,
        private readonly bool $isNew,
        private readonly bool $savingResult,
        private readonly ?string $errorMessage = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getUser(): array
    {
        return $this->user;
    }

    public function getIsNew(): bool
    {
        return $this->isNew;
    }

    public function getSavingResult(): bool
    {
        return $this->savingResult;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage ?? '';
    }
}
