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
 * Contract for the persistence layer the sync engine writes through.
 *
 * Exists as an interface (not just a concrete class) so the engine can be
 * unit-tested against an in-memory implementation without spinning up a
 * real Joomla DB. Production implementation is
 * {@see DatabaseMemberRepository}.
 *
 * @since  __DEPLOY_VERSION__
 */
interface MemberRepositoryInterface
{
    /**
     * Insert or update a member row keyed on `pc_person_id`.
     *
     * Returns the post-operation outcome so the engine can increment the
     * right counter on its {@see SyncReport}.
     *
     * @param   array<string, mixed>  $attrs  Column → value pairs. Must include
     *                                         `pc_person_id`. Anything absent
     *                                         is left untouched on update.
     *
     * @return  UpsertOutcome
     *
     * @since   __DEPLOY_VERSION__
     */
    public function upsertByPcPersonId(array $attrs): UpsertOutcome;

    /**
     * Hard-delete every PC-synced member row whose `pc_person_id` is set but
     * is NOT in the given list of "seen" ids — the spec §5.4 sweep step. These
     * are people who went inactive in PC (active-only fetch no longer returns
     * them) or left the org. Re-activating in PC re-syncs a fresh row.
     *
     * Manual (non-PC) rows are never touched. Empty seen list is a no-op.
     *
     * @param   list<int>  $seenPcPersonIds  PC person ids that DID match the
     *                                        current filter on this run.
     *
     * @return  int  Number of rows deleted.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function deleteMissingPcPersonIds(array $seenPcPersonIds): int;

    /**
     * Phase D: look up a local member row id by PC person id. Used after
     * `upsertByPcPersonId()` so the engine can hand the local id to
     * {@see CustomFieldWriterInterface::setFieldValue()}.
     *
     * @param   int  $pcPersonId
     *
     * @return  int|null  Local row id, or null when no row exists for that
     *                    PC person id.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function findIdByPcPersonId(int $pcPersonId): ?int;

    /**
     * Phase E: persist the cached photo's relative path + URL hash for a
     * PC-synced member row. Called by the sync engine after
     * {@see PhotoCacheInterface::cache()} returns a downloaded result.
     *
     * @param   int     $pcPersonId    PC person id (row lookup key).
     * @param   string  $relativePath  Path under `media/com_cwmconnect/photos/`
     *                                  to store in `image`.
     * @param   string  $hash          SHA-256 of the source URL to store in
     *                                  `image_hash`.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function updateImageByPcPersonId(int $pcPersonId, string $relativePath, string $hash): void;

    /**
     * Phase E: return the current `image_hash` so the engine can let
     * {@see PhotoCacheInterface::cache()} short-circuit when the PC URL
     * hasn't changed since last sync. Null when no hash is stored yet.
     *
     * @param   int  $pcPersonId
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function findImageHashByPcPersonId(int $pcPersonId): ?string;
}
