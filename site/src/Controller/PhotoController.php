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

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use CWM\Component\Cwmconnect\Site\Helper\PhotoAccess;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;

/**
 * Gated proxy that serves member photos to logged-in members.
 *
 * The photo cache is blocked from direct web access; this is the only public
 * path to a member photo. Guests are refused; members get a photo only for
 * rows they are allowed to see (the photo-not-available placeholder otherwise),
 * while managers see every photo.
 *
 * @since  __DEPLOY_VERSION__
 */
class PhotoController extends BaseController
{
    /**
     * Stream a member photo (or the placeholder) for `task=photo.serve&id=N`.
     *
     * @return  void
     *
     * @throws  NotAllowed
     * @since   __DEPLOY_VERSION__
     */
    public function serve(): void
    {
        $app  = Factory::getApplication();
        $db   = Factory::getContainer()->get(DatabaseInterface::class);
        $user = $app->getIdentity();

        $isLoggedIn = $user !== null && (int) $user->id > 0;

        // External clients (e.g. Google Earth loading the KML feed) have no
        // session, so accept the same signed feed token that authenticates the
        // feed and act as that token's user.
        $tokenUserId = null;

        if (!$isLoggedIn) {
            $token = (string) $this->input->getString('token', '');

            if ($token !== '') {
                $inactivityDays = (int) ComponentHelper::getParams('com_cwmconnect')->get('kml_feed_inactivity_days', 90);
                $tokenRow       = new FeedTokenService($db)->validate($token, $inactivityDays);
                $tokenUserId    = $tokenRow !== null ? (int) $tokenRow->user_id : null;
            }

            if ($tokenUserId === null) {
                throw new NotAllowed('JERROR_ALERTNOAUTHOR', 403);
            }
        }

        $member    = PhotoAccess::loadMember($db, $this->input->getInt('id', 0));
        $isManager = $isLoggedIn && $user->authorise('core.manage', 'com_cwmconnect') === true;
        $viewerId  = $isLoggedIn ? (int) $user->id : (int) $tokenUserId;
        $household = $isManager ? null : PhotoAccess::householdId($db, $viewerId);

        $path = null;

        if (PhotoAccess::canView($isManager, $member, $household)) {
            $image       = (string) ($member->image ?? '');
            $size        = $this->input->getWord('size', '');
            $acceptsWebp = str_contains($this->input->server->getString('HTTP_ACCEPT', ''), 'image/webp');

            // Prefer an optimized web variant (smaller, WebP when accepted);
            // fall back to the full-size original when no variant exists yet
            // (e.g. a photo cached before the variant pipeline landed).
            $path = $size !== '' ? PhotoAccess::resolveVariant($image, $size, $acceptsWebp) : null;
            $path ??= PhotoAccess::resolvePath($image);
        }

        $path ??= PhotoAccess::placeholderPath();

        if ($path === null) {
            $app->setHeader('status', '404', true);
            $app->sendHeaders();
            $app->close();
        }

        PhotoAccess::stream($app, $path);
    }

    /**
     * Stream a household family photo for `task=photo.servehousehold&id=N`
     * (N = family-unit id). Family group photos sit behind the directory's
     * login wall, so any logged-in viewer may see a published household's
     * photo; guests are refused.
     *
     * @return  void
     *
     * @throws  NotAllowed
     * @since   __DEPLOY_VERSION__
     */
    public function servehousehold(): void
    {
        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        if ($user === null || (int) $user->id <= 0) {
            throw new NotAllowed('JERROR_ALERTNOAUTHOR', 403);
        }

        $db        = Factory::getContainer()->get(DatabaseInterface::class);
        $household = PhotoAccess::loadHousehold($db, $this->input->getInt('id', 0));

        $path = null;

        if ($household !== null && (int) ($household->published ?? 0) === 1) {
            $size        = $this->input->getWord('size', '');
            $acceptsWebp = str_contains($this->input->server->getString('HTTP_ACCEPT', ''), 'image/webp');
            $path        = PhotoAccess::resolveHouseholdImage((string) ($household->image ?? ''), $size, $acceptsWebp);
        }

        $path ??= PhotoAccess::placeholderPath();

        if ($path === null) {
            $app->setHeader('status', '404', true);
            $app->sendHeaders();
            $app->close();
        }

        PhotoAccess::stream($app, $path);
    }
}
