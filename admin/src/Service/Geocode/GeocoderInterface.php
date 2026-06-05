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
 * A geocoding backend: turns a member's postal address into coordinates.
 *
 * @since  __DEPLOY_VERSION__
 */
interface GeocoderInterface
{
    /**
     * Resolve an address to coordinates.
     *
     * @param   string  $street   Street address line.
     * @param   string  $city     City / suburb.
     * @param   string  $state    State / region.
     * @param   string  $country  Country.
     *
     * @return  GeocodeResult
     *
     * @since   __DEPLOY_VERSION__
     */
    public function geocode(string $street, string $city, string $state, string $country): GeocodeResult;

    /**
     * Compose the four address parts into a single query string. Shared by the
     * providers and exposed so the worker can short-circuit empty addresses.
     *
     * @param   string  $street   Street address line.
     * @param   string  $city     City / suburb.
     * @param   string  $state    State / region.
     * @param   string  $country  Country.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function composeAddress(string $street, string $city, string $state, string $country): string;
}
