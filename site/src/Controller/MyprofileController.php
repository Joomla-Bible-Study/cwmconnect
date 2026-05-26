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
}
