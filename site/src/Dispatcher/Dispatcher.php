<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\LoginWall;
use CWM\Component\Cwmconnect\Site\Helper\MemberAccess;
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * ComponentDispatcher for com_cwmconnect site.
 *
 * Phase G: every view here is members-only per spec §6.1. The dispatcher
 * is the right place to enforce the wall because it sees every request
 * before any controller runs, including custom MVC entry points.
 *
 * @since  2.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    protected string $defaultController = 'display';

    /**
     * Routes that bypass the login wall. The KML feed authenticates via
     * its own signed token (Phase J) so external KML clients can pull
     * without a Joomla session. Anything else falls through to the wall.
     *
     * @var    array<int, array{view: string, format: string}>
     * @since  __DEPLOY_VERSION__
     */
    private const PUBLIC_ROUTES = [
        ['view' => 'members', 'format' => 'kml'],
        ['view' => 'directory', 'format' => 'kml'],
    ];

    /**
     * Tasks that bypass the login wall for the same reason as
     * {@see self::PUBLIC_ROUTES}, but are addressed by `task=` and so carry no
     * `view` to match on.
     *
     * `photo.serve` backs the photos inside KML balloons: Google Earth fetches
     * each one as a separate session-less request carrying the feed token, so
     * holding it to the wall made every balloon photo 403 for external
     * clients. It authenticates itself (session or feed token, then the
     * per-member visibility gates in PhotoAccess), which is what makes
     * exempting it safe.
     *
     * @var    array<int, string>
     * @since  __DEPLOY_VERSION__
     */
    private const PUBLIC_TASKS = [
        'photo.serve',
    ];

    #[\Override]
    public function dispatch(): void
    {
        if ($this->shouldEnforceWall() && !$this->viewerHasMemberAccess()) {
            // A guest gets the login wall (and returns here after logging in);
            // a logged-in user who simply lacks the configured member view
            // level (e.g. a registered non-member) is refused outright.
            if ((int) ($this->app->getIdentity()?->id ?? 0) === 0) {
                $this->app->enqueueMessage(Text::_('COM_CWMCONNECT_LOGIN_REQUIRED'), 'notice');
                $this->app->redirect(
                    Route::_((string) LoginWall::redirectForGuest(0, (string) Uri::getInstance()), false),
                );

                return;
            }

            throw new \RuntimeException(Text::_('COM_CWMCONNECT_ACCESS_DENIED'), 403);
        }

        parent::dispatch();
    }

    /**
     * Whether the current viewer holds the configured member view level
     * (`member_access`, Registered by default). This is what makes the
     * directory members-only: being logged in isn't enough — a registered
     * non-member without the level is turned away.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function viewerHasMemberAccess(): bool
    {
        return MemberAccess::userHas($this->app->getIdentity());
    }

    /**
     * Decide whether the current request is subject to the wall.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private function shouldEnforceWall(): bool
    {
        $input  = $this->input;
        $view   = (string) $input->getCmd('view', '');
        $format = (string) $input->getCmd('format', 'html');

        foreach (self::PUBLIC_ROUTES as $route) {
            if ($route['view'] === $view && $route['format'] === $format) {
                return false;
            }
        }

        if (\in_array((string) $input->getCmd('task', ''), self::PUBLIC_TASKS, true)) {
            return false;
        }

        return true;
    }
}
