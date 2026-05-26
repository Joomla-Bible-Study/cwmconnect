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

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

/**
 * Feed token list controller with revoke action.
 *
 * @since  __DEPLOY_VERSION__
 */
class FeedtokensController extends AdminController
{
    /**
     * @param   string  $name    Model name.
     * @param   string  $prefix  Class prefix.
     * @param   array   $config  Configuration.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getModel($name = 'Feedtoken', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Revoke selected tokens.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function revoke(): void
    {
        $this->checkToken();

        $ids = (array) $this->input->get('cid', [], 'array');
        ArrayHelper::toInteger($ids);

        $redirect = Route::_('index.php?option=com_cwmconnect&view=feedtokens', false);

        if ($ids === []) {
            $this->app->enqueueMessage(Text::_('JGLOBAL_NO_ITEM_SELECTED'), 'warning');
            $this->setRedirect($redirect);

            return;
        }

        $service = Factory::getContainer()->get(FeedTokenService::class);
        $count   = $service->revoke($ids);

        $this->app->enqueueMessage(Text::plural('COM_CWMCONNECT_FEEDTOKEN_REVOKED_N', $count));
        $this->setRedirect($redirect);
    }
}
