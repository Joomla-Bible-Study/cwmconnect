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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

/**
 * Kml form controller.
 *
 * @since  2.0.0
 */
class KmlController extends FormController
{
    /**
     * @var string
     * @since 2.0.0
     */
    protected $view_list = 'kmls';

    /**
     * Method override to check if you can add a new record.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    protected function allowAdd($data = []): bool
    {
        return parent::allowAdd($data);
    }

    /**
     * Method override to check if you can edit an existing record.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    protected function allowEdit($data = [], $key = 'id'): bool
    {
        $recordId = isset($data[$key]) ? (int) $data[$key] : 0;
        $user     = $this->app->getIdentity();
        $userId   = (int) ($user?->id ?? 0);

        if ($user && $user->authorise('core.edit', 'com_cwmconnect')) {
            return true;
        }

        if ($user && $user->authorise('core.edit.own', 'com_cwmconnect')) {
            $ownerId = isset($data['created_by']) ? (int) $data['created_by'] : 0;

            if (empty($ownerId) && $recordId) {
                $record = $this->getModel()->getItem($recordId);

                if (empty($record)) {
                    return false;
                }

                $ownerId = (int) $record->created_by;
            }

            if ($ownerId === $userId) {
                return true;
            }
        }

        return parent::allowEdit($data, $key);
    }

    /**
     * Method to run batch operations.
     *
     * @param   object|null  $model  The model of the component being processed.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public function batch($model = null): bool
    {
        $this->checkToken();

        $model = $this->getModel('Kml', '', []);

        $this->setRedirect(
            Route::_(
                'index.php?option=com_cwmconnect&view=kmls' . $this->getRedirectToListAppend(),
                false
            )
        );

        return parent::batch($model);
    }
}
