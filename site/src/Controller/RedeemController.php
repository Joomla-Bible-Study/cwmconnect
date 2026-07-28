<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\AccessCode\AccessCodeService;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserFactoryInterface;

/**
 * Accepts the shared access code from a logged-in user and, on a match, puts
 * them into the granted group.
 *
 * This is the one route in the component a logged-in non-member may reach —
 * everything else is behind the member-access wall, and a user who has not
 * redeemed yet is by definition on the wrong side of it. Guests are still
 * turned away: the code is not a login, and handing it to an anonymous visitor
 * would make it one.
 *
 * @since  __DEPLOY_VERSION__
 */
class RedeemController extends BaseController
{
    /**
     * Failed attempts allowed before the form stops accepting tries.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * How long a locked-out user waits, in seconds.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const LOCKOUT_SECONDS = 900;

    /**
     * Validate a submitted code and grant the group on success.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function submit(): void
    {
        $this->checkToken();

        $app     = $this->app;
        $user    = $app->getIdentity();
        $userId  = (int) ($user?->id ?? 0);
        $service = new AccessCodeService(ComponentHelper::getParams('com_cwmconnect'));
        $return  = Route::_('index.php?option=com_cwmconnect&view=redeem', false);

        if ($userId === 0) {
            // A guest has nothing to upgrade; the code is not an alternative
            // to signing in.
            $app->enqueueMessage(Text::_('COM_CWMCONNECT_LOGIN_REQUIRED'), 'notice');
            $app->redirect($return);

            return;
        }

        if (!$service->isEnabled()) {
            $app->enqueueMessage(Text::_('COM_CWMCONNECT_ACCESS_CODE_UNAVAILABLE'), 'warning');
            $app->redirect($return);

            return;
        }

        if ($this->isLockedOut()) {
            $app->enqueueMessage(Text::_('COM_CWMCONNECT_ACCESS_CODE_LOCKED'), 'error');
            $app->redirect($return);

            return;
        }

        if (!$service->matches((string) $this->input->getString('access_code', ''))) {
            $this->recordFailure();

            // Deliberately one message for wrong-code and expired-code: an
            // attacker should not learn that they guessed a code which merely
            // lapsed, and the admin's own status line in Options is the place
            // that distinguishes them.
            $app->enqueueMessage(Text::_('COM_CWMCONNECT_ACCESS_CODE_REJECTED'), 'error');
            $app->redirect($return);

            return;
        }

        $this->clearFailures();
        $service->grant($userId);

        // Authorised view levels are derived from group membership and cached
        // in two places that the grant has just invalidated: Access's statics,
        // and `User::$_authLevels`, which is serialised into the session along
        // with the rest of the user. Leaving either stale sends the member
        // straight back to this form, having apparently succeeded.
        //
        // Both are cleared by seating a freshly loaded user. Note this must
        // *replace* the session user, never null it — nulling logs them out,
        // which reads as "entered the right code, got thrown out".
        Access::clearStatics();

        $app->getSession()->set(
            'user',
            Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId),
        );

        $app->enqueueMessage(Text::_('COM_CWMCONNECT_ACCESS_CODE_ACCEPTED'), 'message');
        $app->redirect(Route::_('index.php?option=com_cwmconnect&view=members', false));
    }

    /**
     * Is this session temporarily barred from trying again?
     *
     * Attempts are counted per session rather than per IP: the endpoint is
     * only reachable by an authenticated user, so the account is the natural
     * subject, and it avoids punishing a whole church behind one NAT.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function isLockedOut(): bool
    {
        $session = $this->app->getSession();
        $count   = (int) $session->get('cwmconnect.redeem.failures', 0);
        $last    = (int) $session->get('cwmconnect.redeem.last', 0);

        if ($count < self::MAX_ATTEMPTS) {
            return false;
        }

        if (time() - $last >= self::LOCKOUT_SECONDS) {
            $this->clearFailures();

            return false;
        }

        return true;
    }

    /**
     * Count a rejected attempt.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function recordFailure(): void
    {
        $session = $this->app->getSession();

        $session->set('cwmconnect.redeem.failures', (int) $session->get('cwmconnect.redeem.failures', 0) + 1);
        $session->set('cwmconnect.redeem.last', time());
    }

    /**
     * Reset the attempt counter.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function clearFailures(): void
    {
        $session = $this->app->getSession();

        $session->set('cwmconnect.redeem.failures', 0);
        $session->set('cwmconnect.redeem.last', 0);
    }
}
