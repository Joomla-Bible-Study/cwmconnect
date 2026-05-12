<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Churchdirectory\Administrator\Model\DatabaseModel;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Database tools controller — runs missing schema updates for the
 * component's tables.
 *
 * @since  2.0.0
 */
class DatabaseController extends BaseController
{
    /**
     * Default view for the database tools page.
     *
     * @var string
     * @since 2.0.0
     */
    protected $default_view = 'database';

    /**
     * Cancel the database fix and return to the control panel.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function cancel(): void
    {
        $this->setRedirect(Route::_('index.php?option=com_churchdirectory&view=cpanel', false));
    }

    /**
     * Apply any missing schema changes to the component's tables.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function fix(): void
    {
        /** @var DatabaseModel $model */
        $model = $this->getModel('Database');
        $model->fix();

        $this->setRedirect(Route::_('index.php?option=com_churchdirectory&view=database', false));
    }
}
