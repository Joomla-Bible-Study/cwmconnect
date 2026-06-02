<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Persistence for Planning Center households, stored as
 * `#__cwmconnect_familyunit` rows keyed by `pc_household_id`. Each synced
 * member's `funitid` points at the local row returned here, which is what the
 * directory uses to group a household together.
 *
 * @since  __DEPLOY_VERSION__
 */
interface HouseholdRepositoryInterface
{
    /**
     * Insert or update the family-unit row for a PC household, matched on
     * `pc_household_id`, stamping `pc_last_synced_at`, and return the local
     * row id so the caller can set each member's `funitid`.
     *
     * @param   array{pc_household_id: int, name: string, alias: string}  $fields
     *
     * @return  int  The local `#__cwmconnect_familyunit.id`.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function upsertByPcHouseholdId(array $fields): int;

    /**
     * The cached image hash for a household, or null when it has no photo yet.
     * Lets the sync skip re-downloading an unchanged family photo.
     *
     * @param   int  $pcHouseholdId
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function findImageHashByPcHouseholdId(int $pcHouseholdId): ?string;

    /**
     * Persist a freshly cached family photo's relative path + hash onto the
     * family-unit row.
     *
     * @param   int     $pcHouseholdId
     * @param   string  $relativePath
     * @param   string  $hash
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function updateImageByPcHouseholdId(int $pcHouseholdId, string $relativePath, string $hash): void;
}
