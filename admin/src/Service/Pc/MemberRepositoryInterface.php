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
     * Mark every member row whose `pc_person_id` is set but is NOT in the
     * given list of "seen" ids. Implements the spec §5.4 sweep step.
     *
     * Archive semantics: `display_in_directory = 0` AND `published = 0`. The
     * row is retained (not deleted) per the default config; hard-delete is a
     * separate Phase-K concern.
     *
     * @param   list<int>  $seenPcPersonIds  PC person ids that DID match the
     *                                        current filter on this run.
     *
     * @return  int  Number of rows newly archived (already-archived rows are
     *                not re-counted).
     *
     * @since   __DEPLOY_VERSION__
     */
    public function archiveMissingPcPersonIds(array $seenPcPersonIds): int;

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
}
