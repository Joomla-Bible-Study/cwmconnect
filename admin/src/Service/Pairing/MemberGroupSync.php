<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pairing;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Keeps a Joomla user's membership of the configured *directory member* group
 * in step with whether they are actually a member.
 *
 * Directory access is a view level, and a view level is granted through a user
 * group — so something has to put real members into that group. Registering an
 * account must not be enough: plenty of people sign up to a church site who
 * should not see every member's address and phone number. Pairing is the
 * signal that already distinguishes them, because a paired row means this
 * Joomla account was matched to a synced Planning Center person.
 *
 * The rule is deliberately derived from the database rather than tracked
 * incrementally, so it is idempotent and self-correcting: call
 * {@see self::reconcile()} after anything that could change a user's pairing
 * and the group ends up right regardless of what it was before, or how the
 * change was made.
 *
 * Disabled (a no-op) until an admin picks the group in Options, so upgrading
 * never silently rearranges anyone's group membership.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class MemberGroupSync
{
    /**
     * @param   DatabaseInterface  $db       Database driver.
     * @param   int                $groupId  The configured member group, or 0
     *                                        when the feature is switched off.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private DatabaseInterface $db, private int $groupId) {}

    /**
     * Put this user in the member group, or take them out, to match whether
     * they currently hold a member row.
     *
     * @param   int  $userId  Joomla user id; ignored when 0.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function reconcile(int $userId): void
    {
        if ($this->groupId <= 0 || $userId <= 0) {
            return;
        }

        if ($this->isMember($userId)) {
            UserHelper::addUserToGroup($userId, $this->groupId);

            return;
        }

        UserHelper::removeUserFromGroup($userId, $this->groupId);
    }

    /**
     * Is this user paired to a member who should have directory access?
     *
     * Requires the row to be published: unpublishing a member is how an admin
     * retires someone, and that should withdraw their access to everyone
     * else's details too. `display_in_directory` is deliberately *not* part of
     * the test — that flag hides a member *from* the directory, it does not
     * mean they may no longer see it.
     *
     * @param   int  $userId  Joomla user id.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function isMember(int $userId): bool
    {
        $query = $this->db->createQuery()
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__cwmconnect_details'))
            ->where($this->db->quoteName('user_id') . ' = :userId')
            ->where($this->db->quoteName('published') . ' = 1')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        return (int) $this->db->setQuery($query)->loadResult() > 0;
    }
}
