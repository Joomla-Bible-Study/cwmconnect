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
 * K.6: `CampusRepositoryInterface` backed by Joomla's `DatabaseInterface`.
 * Campuses live as `#__cwmconnect_dirheader` rows (the table reserves
 * `pc_campus_id` + `pc_last_synced_at`); this adds the synced contact columns.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DatabaseCampusRepository implements CampusRepositoryInterface
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    private const TABLE = '#__cwmconnect_dirheader';

    /**
     * @param   DatabaseInterface  $db  Component database driver.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly DatabaseInterface $db) {}

    public function upsertByPcCampusId(array $fields): void
    {
        $campusId = (int) ($fields['pc_campus_id'] ?? 0);

        if ($campusId <= 0) {
            throw new PcException('Campus upsert requires a positive pc_campus_id.');
        }

        $now        = gmdate('Y-m-d H:i:s');
        $existingId  = $this->findRowIdByCampusId($campusId);

        if ($existingId !== null) {
            $this->updateRow($existingId, $fields, $now);

            return;
        }

        $this->insertRow($fields, $now);
    }

    public function findPrimary(): ?object
    {
        $query = $this->db->createQuery()
            ->select(
                $this->db->quoteName(
                    ['name', 'pc_street', 'pc_city', 'pc_state', 'pc_zip', 'pc_country', 'pc_phone', 'pc_email', 'pc_website'],
                ),
            )
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('pc_campus_id') . ' IS NOT NULL')
            ->order($this->db->quoteName('ordering') . ' ASC')
            ->order($this->db->quoteName('id') . ' ASC')
            ->setLimit(1);

        return $this->db->setQuery($query)->loadObject() ?: null;
    }

    /**
     * Row id of the dirheader linked to a PC campus, or null.
     *
     * @param   int  $campusId
     *
     * @return  int|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function findRowIdByCampusId(int $campusId): ?int
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName(self::TABLE))
            ->where($this->db->quoteName('pc_campus_id') . ' = :campusId')
            ->bind(':campusId', $campusId, ParameterType::INTEGER);

        $id = $this->db->setQuery($query)->loadResult();

        return $id !== null ? (int) $id : null;
    }

    /**
     * @param   int                    $rowId
     * @param   array<string, mixed>   $fields
     * @param   string                 $now
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function updateRow(int $rowId, array $fields, string $now): void
    {
        $query = $this->db->createQuery()
            ->update($this->db->quoteName(self::TABLE))
            ->set([
                $this->db->quoteName('name') . ' = :name',
                $this->db->quoteName('pc_street') . ' = :street',
                $this->db->quoteName('pc_city') . ' = :city',
                $this->db->quoteName('pc_state') . ' = :state',
                $this->db->quoteName('pc_zip') . ' = :zip',
                $this->db->quoteName('pc_country') . ' = :country',
                $this->db->quoteName('pc_phone') . ' = :phone',
                $this->db->quoteName('pc_email') . ' = :email',
                $this->db->quoteName('pc_website') . ' = :website',
                $this->db->quoteName('pc_last_synced_at') . ' = :now',
            ])
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $rowId, ParameterType::INTEGER);

        $this->bindCampusValues($query, $fields, $now);

        $this->db->setQuery($query)->execute();
    }

    /**
     * @param   array<string, mixed>  $fields
     * @param   string                $now
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function insertRow(array $fields, string $now): void
    {
        $columns = [
            'name', 'description', 'metakey', 'metadesc', 'metadata', 'params', 'language', 'published',
            'pc_campus_id', 'pc_street', 'pc_city', 'pc_state', 'pc_zip', 'pc_country', 'pc_phone', 'pc_email', 'pc_website', 'pc_last_synced_at',
        ];

        $query = $this->db->createQuery()
            ->insert($this->db->quoteName(self::TABLE))
            ->columns($this->db->quoteName($columns))
            ->values(
                implode(',', [
                    ':name',
                    $this->db->quote(''),
                    $this->db->quote(''),
                    $this->db->quote(''),
                    $this->db->quote(''),
                    $this->db->quote(''),
                    $this->db->quote('*'),
                    '1',
                    ':campusId',
                    ':street',
                    ':city',
                    ':state',
                    ':zip',
                    ':country',
                    ':phone',
                    ':email',
                    ':website',
                    ':now',
                ]),
            )
            ->bind(':campusId', $fields['pc_campus_id'], ParameterType::INTEGER);

        $this->bindCampusValues($query, $fields, $now);

        $this->db->setQuery($query)->execute();
    }

    /**
     * Bind the shared campus value placeholders used by insert + update.
     *
     * @param   \Joomla\Database\QueryInterface  $query
     * @param   array<string, mixed>             $fields
     * @param   string                           $now
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function bindCampusValues(\Joomla\Database\QueryInterface $query, array $fields, string $now): void
    {
        $query
            ->bind(':name', $fields['name'], ParameterType::STRING)
            ->bind(':street', $fields['pc_street'], ParameterType::STRING)
            ->bind(':city', $fields['pc_city'], ParameterType::STRING)
            ->bind(':state', $fields['pc_state'], ParameterType::STRING)
            ->bind(':zip', $fields['pc_zip'], ParameterType::STRING)
            ->bind(':country', $fields['pc_country'], ParameterType::STRING)
            ->bind(':phone', $fields['pc_phone'], ParameterType::STRING)
            ->bind(':email', $fields['pc_email'], ParameterType::STRING)
            ->bind(':website', $fields['pc_website'], ParameterType::STRING)
            ->bind(':now', $now, ParameterType::STRING);
    }
}
