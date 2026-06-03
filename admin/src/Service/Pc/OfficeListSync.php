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
 * Tags directory members with a church-office role from Planning Center People
 * lists (Elders, Deacons, Deaconess, Treasurer, Church Clerk…). PC's office
 * lists are the authoritative, curated source — far cleaner than parsing the
 * free-text `positions` field — so the printed-directory Officers section reads
 * straight from list membership. Run after the people sync.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OfficeListSync
{
    /**
     * @param   Client             $client
     * @param   DatabaseInterface  $db
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly Client $client,
        private readonly DatabaseInterface $db,
    ) {}

    /**
     * Refresh every PC-synced member's `pc_office_role` from the configured
     * office lists. Cleared first so people removed from a list lose the role.
     *
     * @param   array<int, string>  $officeLists  PC list id => role label.
     *
     * @return  int  Members tagged with at least one office role.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function run(array $officeLists): int
    {
        $this->clear();

        // Accumulate the role label(s) per person across all office lists — a
        // member in both the Deacons and Treasurer lists becomes "Deacon, Treasurer".
        $roles = [];

        foreach ($officeLists as $listId => $label) {
            $listId = (int) $listId;
            $label  = trim((string) $label);

            if ($listId <= 0 || $label === '') {
                continue;
            }

            foreach ($this->client->listResults($listId) as $personId) {
                $roles[$personId][$label] = $label;
            }
        }

        $tagged = 0;

        foreach ($roles as $personId => $labels) {
            if ($this->setRole((int) $personId, implode(', ', $labels))) {
                $tagged++;
            }
        }

        return $tagged;
    }

    /**
     * Clear the office role on every PC-synced row.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function clear(): void
    {
        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_details'))
            ->set($this->db->quoteName('pc_office_role') . ' = ' . $this->db->quote(''))
            ->where($this->db->quoteName('pc_person_id') . ' IS NOT NULL');

        $this->db->setQuery($query)->execute();
    }

    /**
     * Set a member's office role by PC person id.
     *
     * @param   int     $personId
     * @param   string  $role
     *
     * @return  bool  Whether a row was updated.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function setRole(int $personId, string $role): bool
    {
        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_details'))
            ->set($this->db->quoteName('pc_office_role') . ' = :role')
            ->where($this->db->quoteName('pc_person_id') . ' = :pid')
            ->bind(':role', $role)
            ->bind(':pid', $personId, \Joomla\Database\ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();

        return $this->db->getAffectedRows() > 0;
    }
}
