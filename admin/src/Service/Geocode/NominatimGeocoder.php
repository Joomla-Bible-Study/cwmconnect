<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Geocode;

use Joomla\CMS\Http\Http;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * OpenStreetMap Nominatim backend. Free and keyless, but the public server's
 * usage policy requires (a) an identifying User-Agent with a contact, and
 * (b) at most one request per second — so each lookup throttles itself.
 *
 * @see https://operations.osmfoundation.org/policies/nominatim/
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class NominatimGeocoder implements GeocoderInterface
{
    /**
     * @param   Http    $http        HTTP client.
     * @param   string  $userAgent   Identifying User-Agent (with contact) per OSM policy.
     * @param   int     $throttleUs  Microseconds to sleep after each call (>= 1s
     *                                to honour the rate limit). Injectable for tests.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private Http $http,
        private string $userAgent,
        private int $throttleUs = 1_100_000,
    ) {}

    /**
     * @inheritDoc
     *
     * @since   __DEPLOY_VERSION__
     */
    public function geocode(string $street, string $city, string $state, string $country): GeocodeResult
    {
        $address = self::composeAddress($street, $city, $state, $country);

        if ($address === '') {
            return GeocodeResult::notFound('EMPTY', 'No address to geocode.');
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($address);

        try {
            $response = $this->http->get($url, ['User-Agent' => $this->userAgent]);
        } catch (\Throwable $e) {
            $this->throttle();

            return GeocodeResult::error('HTTP_ERROR', $e->getMessage());
        }

        $this->throttle();

        if ((int) $response->code === 429) {
            return GeocodeResult::rateLimited('HTTP 429');
        }

        $json = json_decode((string) $response->body, true);

        if (!\is_array($json)) {
            return GeocodeResult::error('BAD_RESPONSE', 'Response was not valid JSON.');
        }

        return self::parse($json);
    }

    /**
     * Map a decoded Nominatim response into a result. Pure — unit-tested
     * without any network.
     *
     * @param   array<int, mixed>  $json  Decoded JSON body (a list of matches).
     *
     * @return  GeocodeResult
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function parse(array $json): GeocodeResult
    {
        $first = $json[0] ?? null;

        if (\is_array($first) && isset($first['lat'], $first['lon'])) {
            return GeocodeResult::found((float) $first['lat'], (float) $first['lon']);
        }

        return GeocodeResult::notFound();
    }

    /**
     * @inheritDoc
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function composeAddress(string $street, string $city, string $state, string $country): string
    {
        return implode(', ', array_filter(array_map('trim', [$street, $city, $state, $country])));
    }

    /**
     * Sleep to keep within the OSM one-request-per-second limit.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function throttle(): void
    {
        if ($this->throttleUs > 0) {
            usleep($this->throttleUs);
        }
    }
}
