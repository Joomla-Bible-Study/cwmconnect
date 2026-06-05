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
 * Pure address tidying shared by the geocoders. Street geocoders (Nominatim
 * especially) miss when an address carries a secondary unit designator
 * ("Apt 3", "Unit 202", "# 369") or is a PO box (no point to resolve). This
 * normaliser strips unit designators and drops PO-box street lines so the
 * lookup falls back to a city/state pin instead of returning nothing.
 *
 * @since  __DEPLOY_VERSION__
 */
final class AddressNormalizer
{
    /**
     * Secondary-unit keywords kept deliberately unambiguous — common English
     * words (floor, room, lot) are excluded so real street names survive.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const string UNIT_KEYWORDS = 'apt|apartment|unit|ste|suite|bldg|building|dept|department|spc|space|trlr|trailer';

    /**
     * Compose a single query string from the parts, after tidying the street.
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
    public static function compose(string $street, string $city, string $state, string $country): string
    {
        return implode(
            ', ',
            array_filter(array_map('trim', [self::cleanStreet($street), $city, $state, $country])),
        );
    }

    /**
     * Tidy a street line: PO boxes collapse to empty (so the caller falls back
     * to a city/state lookup); unit/apartment/suite designators are removed.
     *
     * @param   string  $street  Raw street line.
     *
     * @return  string  Cleaned street, or '' for a PO box.
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function cleanStreet(string $street): string
    {
        $street = trim($street);

        if ($street === '') {
            return '';
        }

        if (preg_match('/\bp\.?\s*o\.?\s*box\b/i', $street)
            || preg_match('/\bpost\s+office\s+box\b/i', $street)) {
            return '';
        }

        // Drop "Apt 3" / "Unit 202" / "Ste 100" / "Bldg A" …
        $street = preg_replace('/\b(?:' . self::UNIT_KEYWORDS . ')\b\.?\s*#?\s*[\w\-]+/i', '', (string) $street);

        // Drop a bare "# 369" style designator.
        $street = preg_replace('/#\s*[\w\-]+/', '', (string) $street);

        // Tidy doubled spaces and trailing separators.
        $street = preg_replace('/\s{2,}/', ' ', (string) $street);

        return trim((string) $street, " ,.-\t");
    }
}
