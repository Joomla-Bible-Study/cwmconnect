<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase D: production `FieldMapRepositoryInterface` backed by Joomla DB.
 *
 * Read-only on the sync path; the admin Mapping screen owns writes.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DatabaseFieldMapRepository implements FieldMapRepositoryInterface
{
    private const TABLE = '#__cwmconnect_pc_field_map';

    public function __construct(private readonly DatabaseInterface $db) {}

    public function allKeyedByPcFieldId(): array
    {
        $query = $this->db->createQuery()
            ->select([
                $this->db->quoteName('id'),
                $this->db->quoteName('pc_field_id'),
                $this->db->quoteName('pc_field_slug'),
                $this->db->quoteName('pc_field_name'),
                $this->db->quoteName('joomla_field_id'),
            ])
            ->from($this->db->quoteName(self::TABLE));

        $rows = $this->db->setQuery($query)->loadAssocList() ?: [];
        $out  = [];

        foreach ($rows as $row) {
            $pcFieldId = (int) ($row['pc_field_id'] ?? 0);

            if ($pcFieldId <= 0) {
                continue;
            }

            $out[$pcFieldId] = [
                'id'              => (int) ($row['id'] ?? 0),
                'pc_field_id'     => $pcFieldId,
                'pc_field_slug'   => (string) ($row['pc_field_slug'] ?? ''),
                'pc_field_name'   => (string) ($row['pc_field_name'] ?? ''),
                'joomla_field_id' => (int) ($row['joomla_field_id'] ?? 0),
            ];
        }

        return $out;
    }

    public function lockedJoomlaFieldNames(): array
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('f.name'))
            ->from($this->db->quoteName(self::TABLE, 'm'))
            ->join(
                'INNER',
                $this->db->quoteName('#__fields', 'f') . ' ON '
                    . $this->db->quoteName('f.id') . ' = ' . $this->db->quoteName('m.joomla_field_id'),
            )
            ->where($this->db->quoteName('f.state') . ' = 1');

        $names = $this->db->setQuery($query)->loadColumn() ?: [];

        return array_values(array_filter(
            array_map('strval', $names),
            static fn(string $name): bool => $name !== '',
        ));
    }
}
