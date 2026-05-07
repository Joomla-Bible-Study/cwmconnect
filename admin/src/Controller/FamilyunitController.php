<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Familyunit form controller.
 *
 * @since  2.0.0
 */
class FamilyunitController extends FormController
{
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
        $user = $this->app->getIdentity();

        return $user !== null && $user->authorise('core.create', 'com_churchdirectory');
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

        if ($user && $user->authorise('core.edit', 'com_churchdirectory')) {
            return true;
        }

        if ($user && $user->authorise('core.edit.own', 'com_churchdirectory')) {
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
}
