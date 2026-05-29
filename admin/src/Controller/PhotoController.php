<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\PhotoAccess;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;

/**
 * Admin-side gated proxy for member photos (e.g. the member-list thumbnail).
 *
 * The photo cache is blocked from direct web access, and the front-end proxy
 * runs under a separate session, so the backend needs its own delivery path.
 * Gated by `core.manage`; staff see every photo.
 *
 * @since  __DEPLOY_VERSION__
 */
class PhotoController extends BaseController
{
    /**
     * Stream a member photo (or placeholder) for `task=photo.serve&id=N`.
     *
     * @return  void
     *
     * @throws  NotAllowed
     * @since   __DEPLOY_VERSION__
     */
    public function serve(): void
    {
        $app = Factory::getApplication();

        if (!$app->getIdentity()?->authorise('core.manage', 'com_cwmconnect')) {
            throw new NotAllowed('JERROR_ALERTNOAUTHOR', 403);
        }

        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $member = PhotoAccess::loadMember($db, $this->input->getInt('id', 0));

        // Managers see every photo (isManager = true).
        $path = PhotoAccess::canView(true, $member, null)
            ? PhotoAccess::resolvePath((string) ($member->image ?? ''))
            : null;

        $path ??= PhotoAccess::placeholderPath();

        if ($path === null) {
            $app->setHeader('status', '404', true);
            $app->sendHeaders();
            $app->close();
        }

        PhotoAccess::stream($app, $path);
    }
}
