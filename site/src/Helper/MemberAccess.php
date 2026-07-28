<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;

/**
 * The single rule for "may this person see member data?".
 *
 * Membership is expressed as a Joomla **view level** (`member_access`,
 * Registered by default), not as "is logged in": a registered non-member must
 * be turned away. Every entry point that can reach member PII has to apply the
 * same test, so it lives here rather than being re-derived per caller.
 *
 * The KML feed is why this is a helper. That route deliberately bypasses the
 * dispatcher's login wall so external clients (Google Earth) can pull without
 * a Joomla session, which means it must re-apply this check itself — for the
 * session viewer *and* for the member a feed token authorises as.
 *
 * @since  __DEPLOY_VERSION__
 */
final class MemberAccess
{
    /**
     * The view level a viewer must hold to see member data.
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function requiredLevel(): int
    {
        return (int) ComponentHelper::getParams('com_cwmconnect')->get('member_access', 2);
    }

    /**
     * Does this user hold the member view level?
     *
     * @param   User|null  $user  Viewer, or null for a guest.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function userHas(?User $user): bool
    {
        // A guest still holds Public (1), so a Public member_access setting
        // deliberately opens the directory up rather than failing closed.
        $levels = $user ? $user->getAuthorisedViewLevels() : [1];

        return \in_array(self::requiredLevel(), $levels, true);
    }

    /**
     * Does the user with this id hold the member view level?
     *
     * Used for feed tokens, which authorise as their owner: if that member
     * later loses the level, the token must stop serving member data even
     * though the token row itself is still valid and unexpired.
     *
     * @param   int  $userId  Joomla user id; 0 is treated as a guest.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function userIdHas(int $userId): bool
    {
        if ($userId <= 0) {
            return self::userHas(null);
        }

        $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);

        return self::userHas($user->id > 0 ? $user : null);
    }
}
