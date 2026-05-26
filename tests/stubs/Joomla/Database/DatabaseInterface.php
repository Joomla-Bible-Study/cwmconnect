<?php

declare(strict_types=1);

namespace Joomla\Database;

/**
 * Minimal stub of Joomla\Database\DatabaseInterface for unit tests.
 */
interface DatabaseInterface
{
    public function createQuery(): QueryInterface;

    public function setQuery(QueryInterface $query, int $offset = 0, int $limit = 0): static;

    public function loadResult(): mixed;

    public function loadObject(): ?object;

    public function loadObjectList(): array;

    public function execute(): bool;

    public function quoteName(string|array $name, string|array|null $as = null): string|array;

    public function quote(string $text, bool $escape = true): string;

    public function getNullDate(): string;

    public function getPrefix(): string;
}
