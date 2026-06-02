<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * Reconcile tool: lists hand-entered (non-PC) member rows so an admin can
 * delete leftover/test rows or merge a row that duplicates a Planning Center
 * person. "Manual" means `pc_person_id IS NULL` — a row the sync never owns.
 *
 * @since  __DEPLOY_VERSION__
 */
class ReconcileModel extends ListModel
{
    /**
     * @var string
     * @since __DEPLOY_VERSION__
     */
    private const TABLE = '#__cwmconnect_details';

    /**
     * Constructor.
     *
     * @param   array  $config  Configuration array.
     *
     * @throws \Exception
     * @since   __DEPLOY_VERSION__
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'name', 'a.name', 'email_to', 'a.email_to'];
        }

        parent::__construct($config);
    }

    /**
     * Auto-populate the model state.
     *
     * @param   string  $ordering   Ordering column.
     * @param   string  $direction  Ordering direction.
     *
     * @return  void
     *
     * @throws \Exception
     * @since   __DEPLOY_VERSION__
     */
    protected function populateState($ordering = 'a.name', $direction = 'asc'): void
    {
        $this->setState(
            'filter.search',
            $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'),
        );

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');

        return parent::getStoreId($id);
    }

    /**
     * Build the list query — hand-entered member rows only.
     *
     * @return  QueryInterface
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($db->quoteName(['a.id', 'a.name', 'a.email_to', 'a.image', 'a.published', 'a.display_in_directory', 'a.created']))
            ->select($db->quoteName('c.title', 'category_title'))
            ->select($db->quoteName('fu.name', 'funitname'))
            ->from($db->quoteName(self::TABLE, 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__cwmconnect_familyunit', 'fu') . ' ON ' . $db->quoteName('fu.id') . ' = ' . $db->quoteName('a.funitid'))
            ->where($db->quoteName('a.pc_person_id') . ' IS NULL');

        if ($search = (string) $this->getState('filter.search')) {
            $like = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(' . $db->quoteName('a.name') . ' LIKE ' . $like . ' OR ' . $db->quoteName('a.email_to') . ' LIKE ' . $like . ')');
        }

        $query->order($db->escape((string) $this->getState('list.ordering', 'a.name')) . ' ' . $db->escape((string) $this->getState('list.direction', 'ASC')));

        return $query;
    }

    /**
     * Synced PC members as id => label options, for the merge picker.
     *
     * @return  array<int, string>  PC person id → display label.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getSyncedOptions(): array
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['pc_person_id', 'name', 'email_to']))
            ->from($db->quoteName(self::TABLE))
            ->where($db->quoteName('pc_person_id') . ' IS NOT NULL')
            ->order($db->quoteName('name') . ' ASC');

        $options = [];

        foreach ($db->setQuery($query)->loadObjectList() ?: [] as $row) {
            $label = (string) $row->name;

            if ($row->email_to !== '' && $row->email_to !== null) {
                $label .= ' <' . $row->email_to . '>';
            }

            $options[(int) $row->pc_person_id] = $label;
        }

        return $options;
    }

    /**
     * Delete a manual (non-PC) row. Refuses PC-synced rows so the tool can
     * never remove a sync-owned member.
     *
     * @param   int  $id  Local row id.
     *
     * @return  bool  True when a row was deleted.
     *
     * @throws  \RuntimeException
     * @since   __DEPLOY_VERSION__
     */
    public function deleteManual(int $id): bool
    {
        $db = $this->getDatabase();

        if ($id <= 0 || !$this->isManualRow($id)) {
            throw new \RuntimeException('Reconcile can only delete hand-entered (non-PC) rows.');
        }

        $query = $db->createQuery()
            ->delete($db->quoteName(self::TABLE))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('pc_person_id') . ' IS NULL')
            ->bind(':id', $id, ParameterType::INTEGER);

        $db->setQuery($query)->execute();

        return $db->getAffectedRows() > 0;
    }

    /**
     * Merge a manual row into a PC person. When that person already has a
     * synced row, the manual row is a confirmed duplicate: carry over its
     * photo if the synced row has none, then delete the manual row. When the
     * person has no synced row yet, adopt the manual row by stamping its
     * `pc_person_id` so the next sync manages it.
     *
     * @param   int  $manualId     The manual (non-PC) row id.
     * @param   int  $pcPersonId   The target PC person id.
     *
     * @return  string  'merged' (duplicate removed) or 'adopted' (row linked).
     *
     * @throws  \RuntimeException
     * @since   __DEPLOY_VERSION__
     */
    public function mergeManual(int $manualId, int $pcPersonId): string
    {
        $db = $this->getDatabase();

        if ($manualId <= 0 || !$this->isManualRow($manualId)) {
            throw new \RuntimeException('Reconcile can only merge hand-entered (non-PC) rows.');
        }

        if ($pcPersonId <= 0) {
            throw new \RuntimeException('A target Planning Center person is required.');
        }

        $manual    = $this->loadRow($manualId);
        $syncedRow = $this->findRowByPcPersonId($pcPersonId);

        if ($syncedRow === null) {
            // No synced row yet — adopt the manual row under this PC id.
            $update = (object) ['id' => $manualId, 'pc_person_id' => $pcPersonId];
            $db->updateObject(self::TABLE, $update, 'id');

            return 'adopted';
        }

        // Duplicate of an existing synced member: preserve a hand-uploaded
        // photo the synced row lacks, then drop the manual duplicate.
        if ((string) ($syncedRow->image ?? '') === '' && (string) ($manual->image ?? '') !== '') {
            $update = (object) ['id' => (int) $syncedRow->id, 'image' => $manual->image];
            $db->updateObject(self::TABLE, $update, 'id');
        }

        $this->deleteManual($manualId);

        return 'merged';
    }

    /**
     * Whether the row exists and is hand-entered (no PC link).
     *
     * @param   int  $id  Local row id.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function isManualRow(int $id): bool
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName(self::TABLE))
            ->where($db->quoteName('id') . ' = :id')
            ->where($db->quoteName('pc_person_id') . ' IS NULL')
            ->bind(':id', $id, ParameterType::INTEGER);

        return $db->setQuery($query, 0, 1)->loadResult() !== null;
    }

    /**
     * Load a single row's id + image.
     *
     * @param   int  $id  Local row id.
     *
     * @return  object
     *
     * @since   __DEPLOY_VERSION__
     */
    private function loadRow(int $id): object
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'image']))
            ->from($db->quoteName(self::TABLE))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObject() ?: (object) ['id' => $id, 'image' => ''];
    }

    /**
     * Find a row by PC person id.
     *
     * @param   int  $pcPersonId  PC person id.
     *
     * @return  object|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function findRowByPcPersonId(int $pcPersonId): ?object
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'image']))
            ->from($db->quoteName(self::TABLE))
            ->where($db->quoteName('pc_person_id') . ' = :pid')
            ->bind(':pid', $pcPersonId, ParameterType::INTEGER);

        return $db->setQuery($query, 0, 1)->loadObject() ?: null;
    }
}
