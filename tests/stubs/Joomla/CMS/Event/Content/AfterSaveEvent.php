<?php

declare(strict_types=1);

namespace Joomla\CMS\Event\Content;

/**
 * Minimal stub of Joomla\CMS\Event\Content\AfterSaveEvent for unit tests.
 *
 * Mirrors just the getters our plugin handler reads. Production class
 * derives from a Joomla\Event\Event chain we don't need under test; the
 * EventInterface marker satisfies the handler's parameter type.
 */
class AfterSaveEvent implements \Joomla\Event\EventInterface
{
    public function __construct(
        private readonly string $context,
        private readonly object $item,
        private readonly bool $isNew = true,
        private readonly ?array $data = null,
        private readonly bool $savingResult = true,
    ) {}

    public function getContext(): string
    {
        return $this->context;
    }

    public function getItem(): object
    {
        return $this->item;
    }

    public function getIsNew(): bool
    {
        return $this->isNew;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getSavingResult(): bool
    {
        return $this->savingResult;
    }
}
