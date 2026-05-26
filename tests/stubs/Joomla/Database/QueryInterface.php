<?php

declare(strict_types=1);

namespace Joomla\Database;

/**
 * Minimal stub of Joomla\Database\QueryInterface for unit tests.
 */
interface QueryInterface
{
    public function select(string|array $columns): static;

    public function from(string $table): static;

    public function where(string|array $conditions, string $glue = 'AND'): static;

    public function update(string $table): static;

    public function set(string|array $conditions, string $glue = ','): static;

    public function bind(string|array $key, mixed &$value, ParameterType $dataType = ParameterType::STRING, int $length = 0): static;
}
