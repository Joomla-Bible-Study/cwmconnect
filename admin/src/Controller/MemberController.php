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
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

/**
 * Member form controller.
 *
 * @since  2.0.0
 */
class MemberController extends FormController
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
        $user       = $this->app->getIdentity();
        $categoryId = ArrayHelper::getValue(
            $data,
            'catid',
            $this->input->getInt('filter_category_id'),
            'int'
        );
        $allow      = null;

        if ($categoryId) {
            $allow = $user !== null && $user->authorise(
                'core.create',
                $this->option . '.category.' . $categoryId
            );
        }

        if ($allow === null) {
            return parent::allowAdd($data);
        }

        return (bool) $allow;
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

        if (!$recordId) {
            return parent::allowEdit($data, $key);
        }

        $item = $this->getModel()->getItem($recordId);

        if (empty($item)) {
            return false;
        }

        $user   = $this->app->getIdentity();
        $userId = (int) ($user?->id ?? 0);

        if ($user === null) {
            return false;
        }

        $canEditOwn = $user->authorise('core.edit.own', $this->option . '.category.' . (int) $item->catid)
            && (int) $item->created_by === $userId;

        return $canEditOwn || $user->authorise('core.edit', $this->option . '.category.' . (int) $item->catid);
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

        $model = $this->getModel('Member', '', []);

        $this->setRedirect(
            Route::_(
                'index.php?option=com_churchdirectory&view=members' . $this->getRedirectToListAppend(),
                false
            )
        );

        return parent::batch($model);
    }
}
