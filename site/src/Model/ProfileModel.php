<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase 0: single-member public profile. Backs `view=profile&id=N`, the v2
 * replacement for the legacy `member` view the directory used to link to.
 *
 * Loads one published, in-directory member plus the visibility-scoped members
 * of their household (children's names only render for a viewer in the same
 * household, per spec §7.2).
 *
 * @since  __DEPLOY_VERSION__
 */
class ProfileModel extends ItemModel
{
    /**
     * Read the requested member id into state.
     *
     * @param   string  $ordering   Unused.
     * @param   string  $direction  Unused.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $this->setState('profile.id', (int) Factory::getApplication()->getInput()->getInt('id', 0));
    }

    /**
     * Load the target member, or false when not found / not directory-visible.
     *
     * @param   integer|null  $pk  Optional member id (defaults to state).
     *
     * @return  object|false
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getItem($pk = null): object|false
    {
        $id = (int) ($pk ?: $this->getState('profile.id'));

        if ($id <= 0) {
            return false;
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.name'),
                $db->quoteName('a.fname'),
                $db->quoteName('a.nickname'),
                $db->quoteName('a.surname'),
                $db->quoteName('a.lname'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.image'),
                $db->quoteName('a.email_to'),
                $db->quoteName('a.telephone'),
                $db->quoteName('a.mobile'),
                $db->quoteName('a.fax'),
                $db->quoteName('a.address'),
                $db->quoteName('a.suburb'),
                $db->quoteName('a.state'),
                $db->quoteName('a.postcode'),
                $db->quoteName('a.country'),
                $db->quoteName('a.funitid'),
                $db->quoteName('a.con_position'),
                $db->quoteName('a.pc_positions'),
                $db->quoteName('a.pc_office_role'),
                $db->quoteName('a.pc_social'),
                $db->quoteName('a.spouse'),
                $db->quoteName('a.misc'),
                $db->quoteName('a.anniversary'),
                $db->quoteName('a.user_id'),
                $db->quoteName('fu.name', 'household_name'),
            ])
            ->from($db->quoteName('#__cwmconnect_details', 'a'))
            ->join('LEFT', $db->quoteName('#__cwmconnect_familyunit', 'fu') . ' ON fu.id = a.funitid')
            ->where($db->quoteName('a.id') . ' = :id')
            ->where($db->quoteName('a.published') . ' = 1')
            ->where($db->quoteName('a.display_in_directory') . ' = 1')
            ->bind(':id', $id, ParameterType::INTEGER);

        return $db->setQuery($query)->loadObject() ?: false;
    }

    /**
     * Resolve a viewer's own household id from their linked member row.
     *
     * @param   integer  $userId  The viewer's Joomla user id (0 for guests).
     *
     * @return  integer|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function viewerHouseholdId(int $userId): ?int
    {
        if ($userId <= 0) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select($db->quoteName('funitid'))
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->bind(':uid', $userId, ParameterType::INTEGER);

        return (int) ($db->setQuery($query)->loadResult() ?? 0) ?: null;
    }

    /**
     * Members sharing the target's household (excluding the target). Hidden /
     * child rows are included by name only when the viewer is in the same
     * household; otherwise they're filtered out and counted separately.
     *
     * @param   integer  $funitId        The household id.
     * @param   integer  $excludeId      The target member id to omit.
     * @param   boolean  $sameHousehold  Whether the viewer shares the household.
     *
     * @return  list<object>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getHouseholdMembers(int $funitId, int $excludeId, bool $sameHousehold): array
    {
        if ($funitId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select([
                $db->quoteName('id'),
                $db->quoteName('name'),
                $db->quoteName('fname'),
                $db->quoteName('surname'),
                $db->quoteName('alias'),
                $db->quoteName('image'),
                $db->quoteName('display_in_directory'),
                $db->quoteName('is_child'),
            ])
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('funitid') . ' = :fid')
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' <> :exclude')
            ->bind(':fid', $funitId, ParameterType::INTEGER)
            ->bind(':exclude', $excludeId, ParameterType::INTEGER)
            ->order($db->quoteName('surname') . ' ASC, ' . $db->quoteName('name') . ' ASC');

        if (!$sameHousehold) {
            $query->where($db->quoteName('display_in_directory') . ' = 1')
                ->where($db->quoteName('is_child') . ' = 0');
        }

        return $db->setQuery($query)->loadObjectList() ?: [];
    }

    /**
     * Count household members an out-of-household viewer may not see by name
     * (children + hidden rows), for the "…and N others" aggregate.
     *
     * @param   integer  $funitId    The household id.
     * @param   integer  $excludeId  The target member id to omit.
     *
     * @return  integer
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getHiddenHouseholdCount(int $funitId, int $excludeId): int
    {
        if ($funitId <= 0) {
            return 0;
        }

        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('funitid') . ' = :fid')
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('id') . ' <> :exclude')
            ->where('(' . $db->quoteName('display_in_directory') . ' = 0 OR ' . $db->quoteName('is_child') . ' = 1)')
            ->bind(':fid', $funitId, ParameterType::INTEGER)
            ->bind(':exclude', $excludeId, ParameterType::INTEGER);

        return (int) $db->setQuery($query)->loadResult();
    }
}
