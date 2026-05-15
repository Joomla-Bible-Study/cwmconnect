<?php

declare(strict_types=1);

namespace Joomla\CMS\Http;

/**
 * Minimal stub of Joomla\CMS\Http\Http for unit tests. The real class is a
 * thin wrapper around joomla/http; tests subclass this stub to canned-response
 * the methods our code calls. Only the surface our code uses lives here.
 */
class Http
{
    /**
     * @param array<string, string> $headers
     */
    public function get(string $url, array $headers = [], ?int $timeout = null): mixed
    {
        return null;
    }

    /**
     * @param array<string, string> $headers
     */
    public function post(string $url, mixed $data, array $headers = [], ?int $timeout = null): mixed
    {
        return null;
    }
}
