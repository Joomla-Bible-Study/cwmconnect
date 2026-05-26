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

use CWM\Component\Cwmconnect\Site\Model\MembersModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Members list controller with KML feed download task.
 *
 * @since  __DEPLOY_VERSION__
 */
class MembersController extends BaseController
{
    /** @since __DEPLOY_VERSION__ */
    protected $default_view = 'members';

    /**
     * Serve a NetworkLink KML file with the user's feed token baked in.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function kmlFeed(): void
    {
        $user = $this->app->getIdentity();

        if (!$user || (int) $user->id <= 0) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        /** @var MembersModel $model */
        $model = $this->getModel('Members');
        $kml   = $model->buildKmlFeedFile((int) $user->id, $user->username ?? 'user');

        $this->app->setHeader('Content-Type', 'application/vnd.google-earth.kml+xml; charset=UTF-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="church-directory-feed.kml"', true);
        $this->app->sendHeaders();

        echo $kml;
        $this->app->close();
    }
}
