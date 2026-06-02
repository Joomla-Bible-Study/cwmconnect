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

use CWM\Component\Cwmconnect\Administrator\Model\ReconcileModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Reconcile tool controller — delete or merge hand-entered (non-PC) member
 * rows. A guard rejects anyone without delete/edit rights on the component.
 *
 * @since  __DEPLOY_VERSION__
 */
class ReconcileController extends BaseController
{
    /**
     * @var string
     * @since __DEPLOY_VERSION__
     */
    protected $default_view = 'reconcile';

    /**
     * Display the reconcile screen.
     *
     * @param   bool   $cachable   If true, the view output will be cached.
     * @param   array  $urlparams  An array of safe URL parameters.
     *
     * @return  static
     *
     * @since   __DEPLOY_VERSION__
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $this->input->set('view', 'reconcile');

        return parent::display($cachable, $urlparams);
    }

    /**
     * Delete a single hand-entered row.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    public function delete(): void
    {
        $this->assertAllowed();

        $id = $this->input->getInt('id', 0);

        try {
            /** @var ReconcileModel $model */
            $model   = $this->getModel('Reconcile');
            $deleted = $model->deleteManual($id);

            $this->setMessage(
                $deleted
                    ? Text::_('COM_CWMCONNECT_RECONCILE_DELETED')
                    : Text::_('COM_CWMCONNECT_RECONCILE_NOTHING'),
                $deleted ? 'message' : 'warning',
            );
        } catch (\Throwable $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=reconcile', false));
    }

    /**
     * Merge a hand-entered row into a PC person.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    public function merge(): void
    {
        $this->assertAllowed();

        $id         = $this->input->getInt('id', 0);
        $pcPersonId = $this->input->getInt('pc_person_id', 0);

        try {
            /** @var ReconcileModel $model */
            $model   = $this->getModel('Reconcile');
            $outcome = $model->mergeManual($id, $pcPersonId);

            $this->setMessage(
                Text::_(
                    $outcome === 'adopted'
                        ? 'COM_CWMCONNECT_RECONCILE_ADOPTED'
                        : 'COM_CWMCONNECT_RECONCILE_MERGED',
                ),
            );
        } catch (\Throwable $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=reconcile', false));
    }

    /**
     * CSRF + ACL guard shared by the mutating tasks.
     *
     * @return  void
     *
     * @throws  \Exception  On a bad token or insufficient rights.
     * @since   __DEPLOY_VERSION__
     */
    private function assertAllowed(): void
    {
        if (!Session::checkToken()) {
            throw new \Exception(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $user = $this->app->getIdentity();

        if (!$user || (!$user->authorise('core.delete', 'com_cwmconnect') && !$user->authorise('core.edit', 'com_cwmconnect'))) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
    }
}
