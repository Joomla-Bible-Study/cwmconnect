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
 * Google Maps Geocoding API backend (JSON endpoint). Requires an API key from a
 * Google Cloud project with the Geocoding API enabled and billing active.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class GoogleGeocoder implements GeocoderInterface
{
    /**
     * @param   Http    $http    HTTP client.
     * @param   string  $apiKey  Google Geocoding API key.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private Http $http, private string $apiKey) {}

    /**
     * @inheritDoc
     *
     * @since   __DEPLOY_VERSION__
     */
    public function geocode(string $street, string $city, string $state, string $country): GeocodeResult
    {
        if ($this->apiKey === '') {
            return GeocodeResult::error('REQUEST_DENIED', 'No Google Geocoding API key is configured.');
        }

        $address = self::composeAddress($street, $city, $state, $country);

        if ($address === '') {
            return GeocodeResult::notFound('EMPTY', 'No address to geocode.');
        }

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='
            . rawurlencode($address) . '&key=' . rawurlencode($this->apiKey);

        try {
            $response = $this->http->get($url);
        } catch (\Throwable $e) {
            return GeocodeResult::error('HTTP_ERROR', $e->getMessage());
        }

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
     * Map a decoded Google Geocoding response into a result. Pure — unit-tested
     * without any network.
     *
     * @param   array<string, mixed>  $json  Decoded JSON body.
     *
     * @return  GeocodeResult
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function parse(array $json): GeocodeResult
    {
        $status  = (string) ($json['status'] ?? '');
        $message = (string) ($json['error_message'] ?? '');

        if ($status === 'OK') {
            $location = $json['results'][0]['geometry']['location'] ?? null;

            if (\is_array($location) && isset($location['lat'], $location['lng'])) {
                return GeocodeResult::found((float) $location['lat'], (float) $location['lng']);
            }

            return GeocodeResult::error('OK', 'Result contained no location.');
        }

        if ($status === 'OVER_QUERY_LIMIT') {
            return GeocodeResult::rateLimited($message);
        }

        if ($status === 'ZERO_RESULTS') {
            return GeocodeResult::notFound($status, $message);
        }

        return GeocodeResult::error($status !== '' ? $status : 'UNKNOWN', $message);
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
}
