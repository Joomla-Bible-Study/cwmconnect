<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Privacy;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\CachedPhotos;
use CWM\Component\Cwmconnect\Administrator\Service\Search\SearchIndexInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * DB-backed {@see PrivacySuppressionsInterface}, on
 * `#__cwmconnect_privacy_suppressions`. The suppression is kept apart from
 * the member row so that deleting the blanked row does not lift it.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class DatabasePrivacySuppressions implements PrivacySuppressionsInterface
{
    /**
     * @param   DatabaseInterface          $db           Database driver.
     * @param   CachedPhotos|null          $photos       The member photo cache, for erasing
     *                                                   a row again. Null leaves the photos.
     * @param   SearchIndexInterface|null  $searchIndex  Smart Search, for erasing a row
     *                                                   again. Null leaves its copy.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private DatabaseInterface $db,
        private ?CachedPhotos $photos = null,
        private ?SearchIndexInterface $searchIndex = null,
    ) {}

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function stateOf(int $pcPersonId): ?bool
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('released_at'))
            ->from($this->db->quoteName('#__cwmconnect_privacy_suppressions'))
            ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
            ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

        $row = $this->db->setQuery($query)->loadObject();

        return $row === null ? null : $row->released_at !== null;
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function suppress(int $pcPersonId): void
    {
        if ($pcPersonId <= 0) {
            return;
        }

        if ($this->stateOf($pcPersonId) !== null) {
            $query = $this->db->createQuery()
                ->update($this->db->quoteName('#__cwmconnect_privacy_suppressions'))
                ->set($this->db->quoteName('released_at') . ' = NULL')
                ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
                ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

            $this->db->setQuery($query)->execute();

            return;
        }

        $row = (object) [
            'pc_person_id' => $pcPersonId,
            'removed_at'   => gmdate('Y-m-d H:i:s'),
        ];

        $this->db->insertObject('#__cwmconnect_privacy_suppressions', $row);
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function releaseMembers(array $memberIds): int
    {
        $now   = gmdate('Y-m-d H:i:s');
        $query = $this->forMembers($memberIds);

        if ($query === null) {
            return 0;
        }

        $query->set($this->db->quoteName('released_at') . ' = :now')
            ->where($this->db->quoteName('released_at') . ' IS NULL')
            ->bind(':now', $now, ParameterType::STRING);

        $this->db->setQuery($query)->execute();

        return (int) $this->db->getAffectedRows();
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function unreleaseMembers(array $memberIds): void
    {
        $query = $this->forMembers($memberIds);

        if ($query === null) {
            return;
        }

        $query->set($this->db->quoteName('released_at') . ' = NULL')
            ->where($this->db->quoteName('released_at') . ' IS NOT NULL');

        $this->db->setQuery($query)->execute();
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function ensureRemoved(int $pcPersonId): void
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__cwmconnect_details'))
            ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
            ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

        $memberIds = $this->db->setQuery($query)->loadColumn();

        if ($memberIds !== []) {
            $erasure = new MemberErasure($this->db, $this, $this->photos, $this->searchIndex);

            foreach ($memberIds as $memberId) {
                $erasure->erase((int) $memberId);
            }

            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $row = (object) (MemberErasure::removedRow($this->db, $pcPersonId) + [
            'pc_person_id' => $pcPersonId,
            'access'       => 2,
            'created'      => $now,
            'modified'     => $now,
        ]);

        $this->db->insertObject('#__cwmconnect_details', $row);
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function completeRestore(int $pcPersonId): void
    {
        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_details'))
            ->set($this->db->quoteName('display_in_directory') . ' = 1')
            ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
            ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();

        $query = $this->db->createQuery()
            ->delete($this->db->quoteName('#__cwmconnect_privacy_suppressions'))
            ->where($this->db->quoteName('pc_person_id') . ' = :pcPersonId')
            ->bind(':pcPersonId', $pcPersonId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }

    /**
     * An UPDATE of the suppressions behind the given member rows, or null when
     * there are none to touch.
     *
     * @param   list<int>  $memberIds  Member row ids.
     *
     * @return  QueryInterface|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function forMembers(array $memberIds): ?QueryInterface
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $memberIds), static fn(int $id): bool => $id > 0)));

        if ($ids === []) {
            return null;
        }

        // Ids inline, not bound: a subquery's bound values don't carry over
        // into the query it is embedded in.
        $members = $this->db->createQuery()
            ->select($this->db->quoteName('pc_person_id'))
            ->from($this->db->quoteName('#__cwmconnect_details'))
            ->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
            ->where($this->db->quoteName('pc_person_id') . ' IS NOT NULL');

        return $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_privacy_suppressions'))
            ->where($this->db->quoteName('pc_person_id') . ' IN (' . $members . ')');
    }
}
