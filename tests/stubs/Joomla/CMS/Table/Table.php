<?php

declare(strict_types=1);

namespace Joomla\CMS\Table;

use Joomla\Database\DatabaseInterface;

/**
 * Minimal stub of Joomla\CMS\Table\Table for unit tests.
 */
abstract class Table
{
    protected DatabaseInterface $_db;
    protected string $_tbl;
    protected string $_tbl_key;
    protected array $_errors = [];
    protected array $_jsonEncode = [];

    public function __construct(string $table, string $key, DatabaseInterface $db)
    {
        $this->_tbl     = $table;
        $this->_tbl_key = $key;
        $this->_db      = $db;
    }

    public function getDatabase(): DatabaseInterface
    {
        return $this->_db;
    }

    public function setError(string $error): void
    {
        $this->_errors[] = $error;
    }

    public function getError(?int $i = null, bool $toString = true): string
    {
        return end($this->_errors) ?: '';
    }

    public function getErrors(): array
    {
        return $this->_errors;
    }

    public function check(): bool
    {
        return true;
    }

    public function load($keys = null, $reset = true): bool
    {
        return true;
    }

    public function store($updateNulls = false): bool
    {
        return true;
    }

    public function reset(): void {}

    public function bind($array, $ignore = ''): bool
    {
        return true;
    }
}
