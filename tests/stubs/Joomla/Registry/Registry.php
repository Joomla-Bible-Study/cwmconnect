<?php

declare(strict_types=1);

namespace Joomla\Registry;

/**
 * Minimal stub of Joomla\Registry\Registry for unit tests.
 */
class Registry
{
    private array $data = [];

    public function __construct(mixed $data = null)
    {
        if (\is_array($data)) {
            $this->data = $data;
        } elseif (\is_string($data) && $data !== '') {
            $this->data = json_decode($data, true) ?? [];
        }
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return $this->data[$path] ?? $default;
    }

    public function set(string $path, mixed $value): mixed
    {
        $this->data[$path] = $value;

        return $value;
    }

    public function loadString(string $data, string $format = 'JSON'): static
    {
        $this->data = json_decode($data, true) ?? [];

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return json_encode($this->data) ?: '{}';
    }
}
