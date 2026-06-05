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

use CWM\Component\Cwmconnect\Site\Model\MyprofileModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Phase H: self-service portal controller. Renders `view=myprofile` and
 * accepts saves from the portal form.
 *
 * The login wall is enforced in the site Dispatcher (Phase G); this
 * controller assumes a logged-in user.
 *
 * @since  2.0.0
 */
class MyprofileController extends BaseController
{
    /**
     * @param   string                $name    Model name.
     * @param   string                $prefix  Class prefix (unused with PSR-4 MVCFactory).
     * @param   array<string, mixed>  $config  Extra configuration.
     *
     * @return  BaseDatabaseModel|false
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Myprofile', $prefix = '', $config = [])
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true] + $config);
    }

    /**
     * Persist a portal edit. Re-renders the form on validation/lock failure
     * with the spec §8.3 flash message; redirects back to the portal on
     * success.
     *
     * @since  2.0.0
     */
    public function save(): void
    {
        Session::checkToken();

        $app  = $this->app;
        $data = (array) $app->getInput()->post->get('jform', [], 'array');

        /** @var MyprofileModel $model */
        $model  = $this->getModel('Myprofile');
        $result = $model->save($data);

        $redirect = Route::_('index.php?option=com_cwmconnect&view=myprofile', false);

        if (!$result) {
            $app->setUserState('com_cwmconnect.myprofile.data', $data);
            $app->enqueueMessage(
                $model->getError() ?: Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_SAVE'),
                'error',
            );
            $this->setRedirect($redirect);

            return;
        }

        $app->setUserState('com_cwmconnect.myprofile.data', null);
        $app->enqueueMessage(Text::_('COM_CWMCONNECT_MYPROFILE_SAVE_SUCCESS'), 'message');
        $this->setRedirect($redirect);
    }

    /**
     * Create a new named live map feed for the current user and stash the
     * one-time cleartext URL for the portal to show once.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function createKmlFeed(): void
    {
        Session::checkToken();

        $input = $this->app->getInput();
        $label = (string) $input->getString('feed_label', '');

        /** @var MyprofileModel $model */
        $model = $this->getModel('Myprofile');

        try {
            $result = $model->createFeed($label, $this->parseExpiry((string) $input->getString('feed_expires', '')));
            $this->app->setUserState('com_cwmconnect.myprofile.feed_cleartext', $result['cleartext']);
            $this->app->enqueueMessage(Text::_('COM_CWMCONNECT_MYPROFILE_FEED_CREATED'));
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect($this->portalRoute());
    }

    /**
     * Rotate one of the current user's feeds and stash the fresh cleartext URL.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function regenerateKmlFeed(): void
    {
        Session::checkToken();

        $id = (int) $this->app->getInput()->getInt('feed_id', 0);

        /** @var MyprofileModel $model */
        $model = $this->getModel('Myprofile');

        try {
            $cleartext = $model->regenerateFeed($id);
            $this->app->setUserState('com_cwmconnect.myprofile.feed_cleartext', $cleartext);
            $this->app->enqueueMessage(Text::_('COM_CWMCONNECT_MYPROFILE_FEED_REGENERATED'));
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect($this->portalRoute());
    }

    /**
     * Revoke a single feed owned by the current user.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function revokeKmlFeed(): void
    {
        Session::checkToken();

        $id = (int) $this->app->getInput()->getInt('feed_id', 0);

        /** @var MyprofileModel $model */
        $model = $this->getModel('Myprofile');

        try {
            $model->revokeFeed($id);
            $this->app->enqueueMessage(Text::_('COM_CWMCONNECT_MYPROFILE_FEED_REVOKED'));
        } catch (\RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect($this->portalRoute());
    }

    /**
     * Revoke every active feed for the current user (panic button).
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function revokeKml(): void
    {
        Session::checkToken();

        /** @var MyprofileModel $model */
        $model = $this->getModel('Myprofile');
        $model->revokeAllFeeds();

        $this->app->enqueueMessage(Text::_('COM_CWMCONNECT_MYPROFILE_KML_REVOKED'));
        $this->setRedirect($this->portalRoute());
    }

    /**
     * Normalise a user-supplied 'YYYY-MM-DD' expiry into an end-of-day UTC
     * timestamp, or null when blank/invalid.
     *
     * @param   string  $raw  Raw date input.
     *
     * @return  string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function parseExpiry(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($raw . ' 23:59:59', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Route back to the portal.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private function portalRoute(): string
    {
        return Route::_('index.php?option=com_cwmconnect&view=myprofile', false);
    }
}
