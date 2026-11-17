<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Access;

use CWM\Component\Cwmconnect\Administrator\Service\Search\SearchIndexInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Moves members to a new member view level when an administrator changes it.
 *
 * Every member gets the member view level (Options → Member view level):
 * synced members at each sync, hand-entered ones when they're created. A
 * member's own access is what a site search and the birthday module check,
 * so when the level changes, the members still at the old one move to the
 * new one at once, rather than synced ones at the next sync and hand-entered
 * ones never. A member at any other level was put there deliberately, and
 * stays.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class MemberViewLevel
{
    /**
     * @param   DatabaseInterface     $db           Database driver.
     * @param   SearchIndexInterface  $searchIndex  Smart Search, which indexes each member
     *                                              under their access level.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private DatabaseInterface $db,
        private SearchIndexInterface $searchIndex,
    ) {}

    /**
     * Move every member at one view level to another.
     *
     * @param   int  $from  The member view level until now.
     * @param   int  $to    The new member view level.
     *
     * @return  int  How many members moved.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function change(int $from, int $to): int
    {
        if ($from === $to || $to <= 0) {
            return 0;
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__cwmconnect_details'))
            ->where($this->db->quoteName('access') . ' = :from')
            ->bind(':from', $from, ParameterType::INTEGER);

        $ids = array_map('intval', $this->db->setQuery($query)->loadColumn());

        if ($ids === []) {
            return 0;
        }

        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_details'))
            ->set($this->db->quoteName('access') . ' = :to')
            ->whereIn($this->db->quoteName('id'), $ids)
            ->bind(':to', $to, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();

        foreach ($ids as $id) {
            $this->searchIndex->memberChanged($id);
        }

        return \count($ids);
    }
}
