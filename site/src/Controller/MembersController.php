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
use CWM\Component\Cwmconnect\Site\Model\MyprofileModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Members list controller with the "name + download a live map feed" task.
 *
 * @since  __DEPLOY_VERSION__
 */
class MembersController extends BaseController
{
    /** @since __DEPLOY_VERSION__ */
    protected $default_view = 'members';

    /**
     * Quick path from the directory: create a named live map feed for the
     * current user (cap-checked, same model as the My Profile panel) and stream
     * the NetworkLink .kml. The feed then shows up in the member's panel for
     * management. On the cap error we redirect back with a message instead of
     * streaming.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function kmlFeed(): void
    {
        Session::checkToken();

        $user = $this->app->getIdentity();

        if (!$user || (int) $user->id <= 0) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $label = (string) $this->app->getInput()->getString('feed_label', '');

        /** @var MyprofileModel $profile */
        $profile = $this->getModel('Myprofile');

        try {
            $result = $profile->createFeed($label);
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=members', false));

            return;
        }

        /** @var MembersModel $model */
        $model = $this->getModel('Members');
        $kml   = $model->buildNetworkLinkDocument($result['cleartext']);

        $this->app->setHeader('Content-Type', 'application/vnd.google-earth.kml+xml; charset=UTF-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="church-directory-feed.kml"', true);
        $this->app->sendHeaders();

        echo $kml;
        $this->app->close();
    }
}
