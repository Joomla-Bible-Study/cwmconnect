<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Administrator\Model\GeoupdateModel;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Controller for the geocoding batch worker.
 *
 * The geocoding pass runs in repeated short slices so the browser can poll
 * progress without exhausting PHP's max_execution_time. The "browse" task
 * resets the work queue and runs the first slice; subsequent calls hit
 * "run" until the queue drains.
 *
 * @since  2.0.0
 */
class GeoupdateController extends BaseController
{
    /**
     * Default view for the geocoding worker.
     *
     * @var string
     * @since 2.0.0
     */
    protected $default_view = 'geoupdate';

    /**
     * Execute a task. Anything but "run" is normalized to "browse" so the
     * legacy popup URL (no task) lands on the reset+display path.
     *
     * @param   string  $task  The task to execute.
     *
     * @return  mixed
     *
     * @since   2.0.0
     */
    public function execute($task): mixed
    {
        if ($task !== 'run') {
            $task = 'browse';
        }

        return parent::execute($task);
    }

    /**
     * Reset the queue and start scanning. First slice runs synchronously and
     * the view template re-submits the form to "run" until empty.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function browse(): void
    {
        $input = $this->app->getInput();
        $id    = (int) $input->getInt('id', 0);

        $input->set('view', 'geoupdate');

        /** @var GeoupdateModel $model */
        $model = $this->getModel('Geoupdate');
        $state = $model->startScanning($id ?: null);
        $input->set('scanstate', $state ? 'start' : 'done');

        $this->display(false);
    }

    /**
     * Run a single slice through the queue.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function run(): void
    {
        $input = $this->app->getInput();
        $id    = (int) $input->getInt('id', 0);

        $input->set('view', 'geoupdate');

        /** @var GeoupdateModel $model */
        $model = $this->getModel('Geoupdate');
        $state = $model->run(true, $id ?: null);
        $input->set('scanstate', $state ? 'running' : 'done');

        $this->display(false);
    }
}
