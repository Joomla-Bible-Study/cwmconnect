<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Geocode;

use Joomla\CMS\Http\HttpFactory;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Builds the configured geocoder from the component params.
 *
 * @since  __DEPLOY_VERSION__
 */
final class GeocoderFactory
{
    /**
     * @param   Registry     $params         Component params.
     * @param   string|null  $fallbackEmail  Contact email used for the Nominatim
     *                                         User-Agent when none is configured
     *                                         (e.g. the site `mailfrom`).
     *
     * @return  GeocoderInterface
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function fromParams(Registry $params, ?string $fallbackEmail = null): GeocoderInterface
    {
        $http = HttpFactory::getHttp();

        if ((string) $params->get('geocode_provider', 'nominatim') === 'google') {
            return new GoogleGeocoder($http, trim((string) $params->get('geocode_api_key', '')));
        }

        $contact = trim((string) $params->get('geocode_contact_email', ''));

        if ($contact === '') {
            $contact = trim((string) ($fallbackEmail ?? ''));
        }

        $userAgent = 'cwmconnect church-directory geocoder'
            . ($contact !== '' ? ' (' . $contact . ')' : '');

        return new NominatimGeocoder($http, $userAgent);
    }
}
