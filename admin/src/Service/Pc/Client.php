<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ApiException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\AuthenticationException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\PcException;
use Joomla\CMS\Http\Http;

\defined('_JEXEC') or die;

/**
 * Planning Center People API client.
 *
 * Stateless wrapper around Joomla's HTTP client that knows how to:
 *   - sign requests with a Personal Access Token (per the spec §11 decision
 *     to default to PAT for v1; OAuth deferred)
 *   - pin the PC API version (`X-PCO-API-Version: 2025-11-10`) so a future
 *     PC default-version bump doesn't surprise us
 *   - turn HTTP-level outcomes into typed exceptions for callers
 *
 * This phase ships the client + DI wiring + tests. The first real consumer
 * lands in Phase C (sync core). Configuration screen storage lives in
 * `com_cwmconnect`'s component params (set via the Options screen).
 *
 * @since 2.0.0
 */
class Client
{
    /**
     * PC People API version pinned via the `X-PCO-API-Version` header.
     * Bumped explicitly when the spec is reviewed and the schema changes
     * have been confirmed harmless or accounted for in our code.
     *
     * @see https://api.planningcenteronline.com/docs/apps/people/versions/2025-11-10
     */
    public const API_VERSION = '2025-11-10';

    public const DEFAULT_BASE_URL = 'https://api.planningcenteronline.com';

    private const DEFAULT_TIMEOUT_SECONDS = 30;

    public function __construct(
        private readonly Http $http,
        private readonly string $personalAccessToken,
        private readonly string $applicationId = '',
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
    ) {
        if ($personalAccessToken === '') {
            throw new Exception\ConfigurationException(
                'Planning Center personal access token is required.',
            );
        }
    }

    /**
     * Fetch `GET /people/v2/me` — the canonical "does my token work" probe.
     * Returns the decoded `data` object of the PC response. Throws
     * {@see AuthenticationException} on 401/403 (bad token), or
     * {@see ApiException} on other HTTP / decoding failure.
     *
     * @return array<string, mixed>
     */
    public function me(): array
    {
        $response = $this->getJson('/people/v2/me');

        if (!isset($response['data']) || !\is_array($response['data'])) {
            throw new ApiException('PC /me response missing "data" envelope.');
        }

        return $response['data'];
    }

    /**
     * Issue a GET against a relative PC API path and decode the JSON body.
     *
     * @param  string                $path   Path relative to the base URL
     *                                       (e.g. `/people/v2/people/123`).
     * @param  array<string, string> $query  Optional query-string params.
     *
     * @return array<string, mixed>  Decoded JSON body.
     */
    public function getJson(string $path, array $query = []): array
    {
        $url = $this->buildUrl($path, $query);

        try {
            $response = $this->http->get($url, $this->buildHeaders(), $this->timeoutSeconds);
        } catch (\Throwable $e) {
            throw new ApiException(
                \sprintf('Transport failure calling PC: %s', $e->getMessage()),
                0,
                null,
                $e,
            );
        }

        return $this->decode($response);
    }

    private function buildUrl(string $path, array $query): string
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Authorization'    => 'Bearer ' . $this->personalAccessToken,
            'Accept'           => 'application/json',
            'X-PCO-API-Version' => self::API_VERSION,
            'User-Agent'       => 'cwmconnect/2.0 (Joomla)',
        ];

        if ($this->applicationId !== '') {
            $headers['X-PCO-Application-Id'] = $this->applicationId;
        }

        return $headers;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $response): array
    {
        $statusCode = (int) ($response->code ?? 0);
        $body       = (string) ($response->body ?? '');

        if ($statusCode === 401 || $statusCode === 403) {
            throw new AuthenticationException(
                \sprintf(
                    'Planning Center rejected the request (HTTP %d). Check the personal access token.',
                    $statusCode,
                ),
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ApiException(
                \sprintf('Planning Center returned HTTP %d.', $statusCode),
                $statusCode,
                $body,
            );
        }

        try {
            $decoded = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiException(
                'Planning Center response was not valid JSON.',
                $statusCode,
                $body,
                $e,
            );
        }

        if (!\is_array($decoded)) {
            throw new ApiException('Planning Center JSON response was not an object.', $statusCode, $body);
        }

        return $decoded;
    }
}
