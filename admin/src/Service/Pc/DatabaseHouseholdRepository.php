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
 * `HouseholdRepositoryInterface` backed by Joomla's `DatabaseInterface`.
 * Households live as `#__cwmconnect_familyunit` rows (the table reserves
 * `pc_household_id` + `pc_last_synced_at`); this links members to them.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DatabaseHouseholdRepository implements HouseholdRepositoryInterface
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    private const TABLE = '#__cwmconnect_familyunit';

    /**
     * Column defaults for an inserted family-unit row. Covers the NOT NULL
     * columns that carry no SQL default, and publishes the household at the
     * public access level so the directory can group it immediately.
     *
     * @var array<string, mixed>
     * @since __DEPLOY_VERSION__
     */
    private const INSERT_DEFAULTS = [
        'description' => '',
        'metakey'     => '',
        'metadesc'    => '',
        'metadata'    => '',
        'params'      => '',
        'published'   => 1,
        'language'    => '*',
        'access'      => 1,
    ];

    /**
     * @param   DatabaseInterface  $db  Component database driver.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly DatabaseInterface $db) {}

    public function upsertByPcHouseholdId(array $fields): int
    {
        $pcHouseholdId = (int) ($fields['pc_household_id'] ?? 0);

        if ($pcHouseholdId <= 0) {
            throw new PcException('Household upsert requires a positive pc_household_id.');
        }

        $now        = gmdate('Y-m-d H:i:s');
        $existingId = $this->findRowIdByHouseholdId($pcHouseholdId);

        // Update touches only the name + sync stamp, leaving alias (SEF-stable)
        // and published (an admin may have unpublished it) untouched. The row
        // must be a variable: updateObject() takes it by reference.
        if ($existingId !== null) {
            $row = (object) [
                'id'                => $existingId,
                'name'              => (string) $fields['name'],
                'pc_last_synced_at' => $now,
                'modified'          => $now,
            ];

            $this->db->updateObject(self::TABLE, $row, 'id');

            return $existingId;
        }

        $row = (object) array_merge(self::INSERT_DEFAULTS, [
            'pc_household_id'   => $pcHouseholdId,
            'name'              => (string) $fields['name'],
            'alias'             => (string) ($fields['alias'] ?? ''),
            'pc_last_synced_at' => $now,
            'created'           => $now,
            'modified'          => $now,
        ]);

        $this->db->insertObject(self::TABLE, $row);

        return (int) $this->db->insertid();
    }

    /**
     * Row id of the family-unit linked to a PC household, or null.
     *
     * @param   int  $pcHouseholdId
     *
     * @return  int|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function findRowIdByHouseholdId(int $pcHouseholdId): ?int
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('pc_household_id') . ' = :householdId')
            ->bind(':householdId', $pcHouseholdId, ParameterType::INTEGER);

        $id = $this->db->setQuery($query)->loadResult();

        return $id !== null ? (int) $id : null;
    }
}
