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

/**
 * Phase G: every front-end view of com_cwmconnect is members-only per
 * spec §6.1. This helper computes the Joomla login URL guests should
 * be redirected to so they land back at the original page after
 * authenticating.
 *
 * Pure — no Application / Factory / DB access. Tests pass the user
 * id and current URL directly; the Dispatcher composes them from
 * runtime state.
 *
 * Routes that bypass the wall (handled by the caller, not here):
 *  - `format=kml&token=...` feed view (Phase J ships its own signed-token
 *    auth so external KML clients work without a Joomla session).
 *  - The standard Joomla login/logout/register views (which we never
 *    serve — they live under com_users).
 *
 * @since  __DEPLOY_VERSION__
 */
final class LoginWall
{
    /**
     * Resolve the redirect URL for a request, or null when the request
     * is allowed through.
     *
     * @param   int      $userId      The viewer's user id. 0 / negative
     *                                 means "guest" — redirect.
     * @param   string   $currentUrl  The URL the guest tried to load
     *                                 (component-internal — `index.php?...`).
     *                                 Captured so post-login lands them
     *                                 back where they were.
     *
     * @return  string|null  Absolute-ish redirect URL when the wall fires,
     *                       or null when the user is allowed through.
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function redirectForGuest(int $userId, string $currentUrl): ?string
    {
        if ($userId > 0) {
            return null;
        }

        return 'index.php?option=com_users&view=login&return=' . base64_encode($currentUrl);
    }
}
