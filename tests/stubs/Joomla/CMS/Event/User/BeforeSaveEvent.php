<?php

declare(strict_types=1);

namespace Joomla\CMS\Event\User;

/**
 * Minimal stub of Joomla\CMS\Event\User\BeforeSaveEvent for unit tests.
 *
 * As in production, getUser() is the account as stored before the save
 * and getData() is what is about to be saved.
 */
class BeforeSaveEvent implements \Joomla\Event\EventInterface
{
    /**
     * @param  array<string, mixed>  $user  The account before the save.
     * @param  array<string, mixed>  $data  The account about to be saved.
     */
    public function __construct(
        private readonly array $user,
        private readonly bool $isNew,
        private readonly array $data,
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

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
