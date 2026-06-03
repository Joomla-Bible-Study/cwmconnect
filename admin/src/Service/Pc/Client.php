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
     * Fetch every Planning Center `FieldDefinition` resource, walking the
     * paginated `/people/v2/field_definitions` index. Used by the admin
     * Mapping screen (Phase D) so administrators can pick a real PC field
     * to pair with a Joomla custom field — no need to type slugs or IDs
     * by hand.
     *
     * Each returned row carries `id`, `slug`, `name`, `data_type` and the
     * resolved tab name (best-effort; empty string when the field has no
     * tab relationship or the included tab is missing).
     *
     * @return  list<array{id: int, slug: string, name: string, data_type: string, tab: string}>
     *
     * @throws  ApiException  On HTTP / transport / decoding failure.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function listFieldDefinitions(): array
    {
        $url  = $this->buildUrl(
            '/people/v2/field_definitions',
            ['include' => 'tab', 'per_page' => '100'],
        );
        $rows = [];
        $hops = 0;

        do {
            if (++$hops > 50) {
                throw new ApiException('PC field_definitions pagination cap reached (50 pages).');
            }

            $page = $this->getJsonAbsolute($url);

            $tabsByKey = $this->indexFieldDefinitionTabs(
                \is_array($page['included'] ?? null) ? $page['included'] : [],
            );

            foreach ((array) ($page['data'] ?? []) as $row) {
                if (!\is_array($row)) {
                    continue;
                }

                $id    = (int) ($row['id'] ?? 0);
                $attrs = \is_array($row['attributes'] ?? null) ? $row['attributes'] : [];

                if ($id <= 0) {
                    continue;
                }

                $rows[] = [
                    'id'        => $id,
                    'slug'      => (string) ($attrs['slug'] ?? ''),
                    'name'      => (string) ($attrs['name'] ?? ''),
                    'data_type' => (string) ($attrs['data_type'] ?? ''),
                    'tab'       => $this->resolveTabName($row, $tabsByKey),
                ];
            }

            $next = \is_array($page['links'] ?? null) ? ($page['links']['next'] ?? null) : null;
            $url  = \is_string($next) && $next !== '' ? $next : null;
        } while ($url !== null);

        return $rows;
    }

    /**
     * Fetch `GET /people/v2/campuses` and return the raw campus resources
     * (each a `data` element with `id` + `attributes`). Follows the
     * `links.next` pagination chain. Used by the campus sync (K.6) to
     * populate the directory cover's church name + address from PC.
     *
     * @return  list<array<string, mixed>>
     *
     * @throws  ApiException  On HTTP / decoding failure or pagination runaway.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function listCampuses(): array
    {
        $url  = $this->buildUrl('/people/v2/campuses', ['per_page' => '100']);
        $rows = [];
        $hops = 0;

        do {
            if (++$hops > 50) {
                throw new ApiException('PC campuses pagination cap reached (50 pages).');
            }

            $page = $this->getJsonAbsolute($url);

            foreach ((array) ($page['data'] ?? []) as $row) {
                if (\is_array($row)) {
                    $rows[] = $row;
                }
            }

            $next = \is_array($page['links'] ?? null) ? ($page['links']['next'] ?? null) : null;
            $url  = \is_string($next) && $next !== '' ? $next : null;
        } while ($url !== null);

        return $rows;
    }

    /**
     * Fetch the PC `Person` ids that belong to a People list, following
     * pagination. Used to map office lists (Elders, Deacons…) to directory roles.
     *
     * @param   int  $listId
     *
     * @return  list<int>
     *
     * @throws  ApiException
     *
     * @since   __DEPLOY_VERSION__
     */
    public function listResults(int $listId): array
    {
        $url  = $this->buildUrl('/people/v2/lists/' . $listId . '/list_results', ['per_page' => '100']);
        $ids  = [];
        $hops = 0;

        do {
            if (++$hops > 100) {
                throw new ApiException('PC list-results pagination cap reached (100 pages).');
            }

            $page = $this->getJsonAbsolute($url);

            foreach ((array) ($page['data'] ?? []) as $row) {
                $personId = $row['relationships']['person']['data']['id'] ?? null;

                if ($personId !== null) {
                    $ids[(int) $personId] = (int) $personId;
                }
            }

            $next = \is_array($page['links'] ?? null) ? ($page['links']['next'] ?? null) : null;
            $url  = \is_string($next) && $next !== '' ? $next : null;
        } while ($url !== null);

        return array_values($ids);
    }

    /**
     * Build a `Tab:<id>` → name lookup from the `included` array on a
     * paginated `/people/v2/field_definitions?include=tab` response.
     *
     * @param   array<int, array<string, mixed>>  $included
     *
     * @return  array<string, string>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function indexFieldDefinitionTabs(array $included): array
    {
        $tabs = [];

        foreach ($included as $resource) {
            if (!\is_array($resource) || ($resource['type'] ?? null) !== 'Tab') {
                continue;
            }

            $id    = $resource['id'] ?? null;
            $attrs = \is_array($resource['attributes'] ?? null) ? $resource['attributes'] : [];

            if (\is_string($id) || \is_int($id)) {
                $tabs['Tab:' . $id] = (string) ($attrs['name'] ?? '');
            }
        }

        return $tabs;
    }

    /**
     * Read the `tab` relationship off a FieldDefinition row and resolve it
     * against the included-tab index. Returns an empty string when the
     * field has no tab or the tab resource wasn't included in the page.
     *
     * @param   array<string, mixed>  $row
     * @param   array<string, string>  $tabsByKey
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function resolveTabName(array $row, array $tabsByKey): string
    {
        $tabRel = $row['relationships']['tab']['data'] ?? null;

        if (!\is_array($tabRel) || !isset($tabRel['type'], $tabRel['id'])) {
            return '';
        }

        return $tabsByKey[$tabRel['type'] . ':' . $tabRel['id']] ?? '';
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
        return $this->getJsonAbsolute($this->buildUrl($path, $query));
    }

    /**
     * Issue a GET against a fully-qualified URL — used to follow `links.next`
     * pagination URLs returned by PC, which arrive absolute. Validates that
     * the URL targets the configured base host so a poisoned `next` value
     * can't redirect us elsewhere.
     *
     * @param   string  $url  Fully-qualified URL (must share host with the
     *                         configured base URL).
     *
     * @return  array<string, mixed>  Decoded JSON body.
     *
     * @throws  ApiException  When the host check fails or the call fails.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getJsonAbsolute(string $url): array
    {
        $this->assertSameHostAsBase($url);

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

    /**
     * Guard against following a PC URL whose host differs from the configured
     * base URL host. Defends against a malicious or buggy `links.next` value.
     *
     * @param   string  $url
     *
     * @return  void
     *
     * @throws  ApiException
     *
     * @since   __DEPLOY_VERSION__
     */
    private function assertSameHostAsBase(string $url): void
    {
        $baseHost = parse_url($this->baseUrl, \PHP_URL_HOST);
        $urlHost  = parse_url($url, \PHP_URL_HOST);

        if (!\is_string($baseHost) || !\is_string($urlHost) || strcasecmp($baseHost, $urlHost) !== 0) {
            throw new ApiException(
                \sprintf('Refusing to follow PC URL with foreign host: %s', (string) $urlHost),
            );
        }
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
            'Authorization'     => 'Basic ' . base64_encode($this->applicationId . ':' . $this->personalAccessToken),
            'Accept'            => 'application/json',
            'X-PCO-API-Version' => self::API_VERSION,
            'User-Agent'        => 'cwmconnect/2.0 (Joomla)',
        ];

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
