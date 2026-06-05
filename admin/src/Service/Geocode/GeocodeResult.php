<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Geocode;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Immutable outcome of a single geocode lookup. Decouples the worker from any
 * one provider's response shape: each provider maps its API result into one of
 * the four factory states (found / not-found / rate-limited / error).
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class GeocodeResult
{
    /**
     * @param   bool        $found        Coordinates were resolved.
     * @param   bool        $rateLimited  Provider asked us to slow down / back off.
     * @param   float|null  $lat          Latitude when found.
     * @param   float|null  $lng          Longitude when found.
     * @param   string      $status       Short machine status (OK, ZERO_RESULTS, …).
     * @param   string      $message      Human-readable detail for the error log.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function __construct(
        public bool $found,
        public bool $rateLimited,
        public ?float $lat,
        public ?float $lng,
        public string $status,
        public string $message,
    ) {}

    /**
     * Coordinates resolved.
     *
     * @param   float  $lat  Latitude.
     * @param   float  $lng  Longitude.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function found(float $lat, float $lng): self
    {
        return new self(true, false, $lat, $lng, 'OK', '');
    }

    /**
     * No match for the address (a real answer, not a failure).
     *
     * @param   string  $status   Provider status.
     * @param   string  $message  Detail.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function notFound(string $status = 'ZERO_RESULTS', string $message = ''): self
    {
        return new self(false, false, null, null, $status, $message);
    }

    /**
     * Provider rate-limited us; the caller should back off and retry.
     *
     * @param   string  $message  Detail.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function rateLimited(string $message = ''): self
    {
        return new self(false, true, null, null, 'RATE_LIMITED', $message);
    }

    /**
     * A genuine error (bad key, transport failure, malformed response).
     *
     * @param   string  $status   Provider/transport status.
     * @param   string  $message  Detail.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function error(string $status, string $message = ''): self
    {
        return new self(false, false, null, null, $status, $message);
    }
}
