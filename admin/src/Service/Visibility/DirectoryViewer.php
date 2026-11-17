<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Visibility;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Who is looking at the directory, as far as members' choices are concerned.
 *
 * Church leadership, the groups given the "See all member details"
 * permission, sees everything. Everyone sees all of their own entry and their
 * own household's. Everyone else sees only what each member chose to show
 * ({@see MemberVisibility}).
 *
 * @since  __DEPLOY_VERSION__
 */
final class DirectoryViewer
{
    /**
     * The permission that lets a group see past members' choices.
     *
     * @since  __DEPLOY_VERSION__
     */
    public const PERMISSION = 'cwmconnect.directory.full';

    /**
     * @param   bool       $seesAll       Whether the viewer holds the permission.
     * @param   list<int>  $memberIds     The members linked to the viewer's account.
     * @param   list<int>  $householdIds  The households those members belong to.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        public readonly bool $seesAll = false,
        public readonly array $memberIds = [],
        public readonly array $householdIds = [],
    ) {}

    /**
     * The viewer behind a Joomla account, or a guest for none.
     *
     * @param   User|null          $user  The account.
     * @param   DatabaseInterface  $db    Database to find their members in.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function forUser(?User $user, DatabaseInterface $db): self
    {
        $userId = (int) ($user->id ?? 0);

        if ($user === null || $userId <= 0) {
            return new self();
        }

        $query = $db->createQuery()
            ->select([$db->quoteName('id'), $db->quoteName('funitid')])
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $rows = $db->setQuery($query)->loadObjectList() ?: [];

        return new self(
            (bool) $user->authorise(self::PERMISSION, 'com_cwmconnect'),
            array_map(static fn(object $row): int => (int) $row->id, $rows),
            array_values(array_filter(array_map(static fn(object $row): int => (int) $row->funitid, $rows))),
        );
    }

    /**
     * The viewer behind an account id: the owner of a map feed's token, which
     * comes without a session.
     *
     * @param   int                $userId  The account's id; 0 for a guest.
     * @param   DatabaseInterface  $db      Database to find their members in.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function forUserId(int $userId, DatabaseInterface $db): self
    {
        if ($userId <= 0) {
            return new self();
        }

        $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);

        return self::forUser($user->id > 0 ? $user : null, $db);
    }

    /**
     * Whether the viewer sees everything about a member, hidden details too.
     *
     * @param   object  $member  A member row with `id` and `funitid`.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function seesAllOf(object $member): bool
    {
        if ($this->seesAll || \in_array((int) ($member->id ?? 0), $this->memberIds, true)) {
            return true;
        }

        $household = (int) ($member->funitid ?? 0);

        return $household > 0 && \in_array($household, $this->householdIds, true);
    }
}
