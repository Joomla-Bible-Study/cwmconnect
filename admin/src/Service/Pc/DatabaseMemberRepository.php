<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\PcException;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Production `MemberRepositoryInterface` implementation backed by Joomla's
 * `DatabaseInterface`.
 *
 * Lives in this file (not under `/Repository/`) because Phase C ships exactly
 * one persistence pathway; if more land later, this is the moment to fan out
 * a sub-namespace.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DatabaseMemberRepository implements MemberRepositoryInterface
{
    /**
     * Fully-qualified table name (Joomla prefix-substituted at query time).
     *
     * @since  __DEPLOY_VERSION__
     */
    private const TABLE = '#__cwmconnect_details';

    /**
     * Defaults for NOT-NULL columns that have no DB-level DEFAULT clause.
     * Without these, INSERTs from a mapped PC payload would fail strict-mode
     * MySQL (the legacy schema predates DEFAULT-everywhere conventions).
     *
     * @var    array<string, mixed>
     * @since  __DEPLOY_VERSION__
     */
    private const INSERT_DEFAULTS = [
        'con_position'     => '',
        'spouse'           => '',
        'children'         => '',
        'sortname1'        => '',
        'sortname2'        => '',
        'sortname3'        => '',
        'created_by_alias' => '',
        'xreference'       => '',
        'attribs'          => '',
        'params'           => '',
        'metakey'          => '',
        'metadesc'         => '',
        'metadata'         => '',
        'lat'              => 0,
        'lng'              => 0,
        'language'         => '*',
        'access'           => 1,
        'published'        => 1,
        'kmlid'            => 1,
    ];

    /**
     * Constructor.
     *
     * @param   DatabaseInterface  $db  Joomla database connection.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly DatabaseInterface $db) {}

    /**
     * Insert or update a member row keyed on `pc_person_id`. Returns whether
     * the row was added, updated in place, or un-archived from a prior sweep.
     *
     * @param   array<string, mixed>  $attrs
     *
     * @return  UpsertOutcome
     *
     * @throws  PcException  When `pc_person_id` is missing or non-positive.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function upsertByPcPersonId(array $attrs): UpsertOutcome
    {
        $pcPersonId = (int) ($attrs['pc_person_id'] ?? 0);

        if ($pcPersonId <= 0) {
            throw new PcException('Cannot upsert PC member: pc_person_id is missing or non-positive.');
        }

        $existing = $this->findExistingByPcPersonId($pcPersonId);

        if ($existing === null) {
            $this->insert($attrs);

            return UpsertOutcome::Added;
        }

        $this->update($existing['id'], $attrs);

        return $existing['display_in_directory'] === 0
            ? UpsertOutcome::Unarchived
            : UpsertOutcome::Updated;
    }

    /**
     * Hard-delete every PC-synced member row whose `pc_person_id` is NOT in
     * the given list — people who went inactive in PC (no longer fetched) or
     * left the org entirely. Re-activating them in PC re-syncs a fresh row.
     * Empty list is treated as a no-op (see implementation note).
     *
     * Only `pc_person_id IS NOT NULL` rows are touched, so hand-entered
     * (manual) members are never deleted by the sweep.
     *
     * @param   list<int>  $seenPcPersonIds
     *
     * @return  int  Number of rows deleted.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function deleteMissingPcPersonIds(array $seenPcPersonIds): int
    {
        // Empty seen list means PC returned zero people. That could mean "the
        // org is empty" or "filter returned nothing"; either way, deleting
        // every existing PC-synced row from a single bad run is almost never
        // what an operator wants. Skip — admin can re-run after fixing the
        // filter.
        if ($seenPcPersonIds === []) {
            return 0;
        }

        $query = $this->db->createQuery();

        $query->delete($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('pc_person_id') . ' IS NOT NULL')
            ->where($this->db->quoteName('pc_person_id') . ' NOT IN (' . implode(',', array_map('intval', $seenPcPersonIds)) . ')');

        $this->db->setQuery($query)->execute();

        return (int) $this->db->getAffectedRows();
    }

    public function findIdByPcPersonId(int $pcPersonId): ?int
    {
        $existing = $this->findExistingByPcPersonId($pcPersonId);

        return $existing === null ? null : $existing['id'];
    }

    public function updateImageByPcPersonId(int $pcPersonId, string $relativePath, string $hash): void
    {
        $query = $this->db->createQuery()
            ->update($this->db->quoteName(self::TABLE))
            ->set([
                $this->db->quoteName('image') . ' = :image',
                $this->db->quoteName('image_hash') . ' = :hash',
            ])
            ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
            ->bind(':image', $relativePath, ParameterType::STRING)
            ->bind(':hash', $hash, ParameterType::STRING)
            ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }

    public function findImageHashByPcPersonId(int $pcPersonId): ?string
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('image_hash'))
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
            ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

        $hash = $this->db->setQuery($query)->loadResult();

        return \is_string($hash) && $hash !== '' ? $hash : null;
    }

    /**
     * Look up the id + archive state of an existing row by PC person id.
     *
     * @param   int  $pcPersonId
     *
     * @return  array{id: int, display_in_directory: int}|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function findExistingByPcPersonId(int $pcPersonId): ?array
    {
        $query = $this->db->createQuery()
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('display_in_directory'),
            ])
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
            ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

        $row = $this->db->setQuery($query)->loadAssoc();

        if (!\is_array($row) || !isset($row['id'])) {
            return null;
        }

        return [
            'id'                   => (int) $row['id'],
            'display_in_directory' => (int) ($row['display_in_directory'] ?? 1),
        ];
    }

    /**
     * Insert a new row, merging in legacy-schema defaults for NOT-NULL
     * columns the mapper doesn't supply.
     *
     * @param   array<string, mixed>  $attrs
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function insert(array $attrs): void
    {
        $now = new \DateTimeImmutable()->format('Y-m-d H:i:s');

        $row = (object) array_merge(self::INSERT_DEFAULTS, [
            'created'  => $now,
            'modified' => $now,
        ], $attrs);

        $this->db->insertObject(self::TABLE, $row);
    }

    /**
     * Update an existing row in place. Bumps `modified`, and force-enables
     * `display_in_directory` + `published` so a previously-swept row comes
     * back when its PC person reappears.
     *
     * @param   int                   $id     Local row id.
     * @param   array<string, mixed>  $attrs  Column → value overrides.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function update(int $id, array $attrs): void
    {
        $attrs['id']       = $id;
        $attrs['modified'] = new \DateTimeImmutable()->format('Y-m-d H:i:s');

        // Visibility is admin-owned once a row exists. The sync sets published
        // + display_in_directory on INSERT (visible by default) but must NOT
        // clobber a later admin hide/unpublish on UPDATE. Inactive people are
        // hard-deleted by the sweep — never kept as published=0 — so there is
        // no PC-driven published change to push here; published=0 on a retained
        // synced row always means "an admin hid this person."
        unset($attrs['display_in_directory'], $attrs['published']);

        $row = (object) $attrs;
        $this->db->updateObject(self::TABLE, $row, 'id');
    }
}
