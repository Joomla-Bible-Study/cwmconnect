<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase G + spec §7.2: decide what household scope a viewer has on a
 * given member's row.
 *
 * Used by the member profile + household browse views to branch on
 * showing children's first names + ages (same-household) vs an
 * aggregate "…and 2 children" pill (everyone else). Pure: caller
 * supplies the viewer's familyunit id (resolved from the viewer's
 * `user_id` → `#__cwmconnect_details.funitid` once at request time)
 * and the target row's familyunit id; helper does no DB I/O.
 *
 * @since  __DEPLOY_VERSION__
 */
final class HouseholdVisibility
{
    /**
     * Result enum. Lives in this class as a top-level enum would force
     * a separate file for trivially three constants.
     *
     * @since  __DEPLOY_VERSION__
     */
    public const string GUEST = 'guest';
    public const string SAME_HOUSEHOLD = 'same_household';
    public const string OTHER_HOUSEHOLD = 'other_household';

    /**
     * @param   int|null  $viewerHouseholdId  Familyunit id the viewer
     *                                         belongs to, or null when
     *                                         the viewer isn't a member
     *                                         themselves.
     * @param   int|null  $targetHouseholdId  Familyunit id of the member
     *                                         being viewed.
     *
     * @return  string  One of GUEST / SAME_HOUSEHOLD / OTHER_HOUSEHOLD.
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function scope(?int $viewerHouseholdId, ?int $targetHouseholdId): string
    {
        if ($viewerHouseholdId === null || $viewerHouseholdId <= 0) {
            return self::GUEST;
        }

        if ($targetHouseholdId === null || $targetHouseholdId <= 0) {
            return self::OTHER_HOUSEHOLD;
        }

        return $viewerHouseholdId === $targetHouseholdId
            ? self::SAME_HOUSEHOLD
            : self::OTHER_HOUSEHOLD;
    }

    /**
     * Whether the viewer should see the target's children by *name*.
     *
     * Convenience for templates so they don't reach for the enum
     * constants directly.
     *
     * @param   string  $scope  Result of {@see scope()}.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function showsChildNames(string $scope): bool
    {
        return $scope === self::SAME_HOUSEHOLD;
    }
}
