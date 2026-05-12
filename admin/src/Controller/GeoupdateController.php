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

use CWM\Component\Cwmconnect\Administrator\Model\GeoupdateModel;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;

/**
 * AJAX endpoints for the geocoding batch worker.
 *
 * The worker runs in repeated short slices (see GeoupdateModel::SLICE_BUDGET)
 * so PHP's max_execution_time isn't a hard limit on the queue. The browser
 * drives the polling via media/com_cwmconnect/js/geoupdate.js:
 *
 *   POST task=geoupdate.start  → reset queue, run first slice, return state
 *   POST task=geoupdate.slice  → run next slice, return state
 *
 * Both endpoints return a JSON document of the form:
 *
 *   { "state": "running"|"done", "total": N, "done": M, "percent": 0-100 }
 *
 * The legacy popup-window-as-view approach (browse/run + auto-resubmitting
 * form, requiring `tmpl=component`) was removed in 2.0.0-dev — no more
 * View/Geoupdate, no more tmpl/geoupdate.
 *
 * @since  2.0.0
 */
class GeoupdateController extends BaseController
{
    /**
     * Reset the queue and run the first slice.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function start(): void
    {
        $this->checkToken('post');

        /** @var GeoupdateModel $model */
        $model = $this->getModel('Geoupdate');
        $id    = (int) $this->app->getInput()->getInt('id', 0);

        $more = $model->startScanning($id ?: null);

        $this->respond($more, $model);
    }

    /**
     * Advance the queue by one slice.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function slice(): void
    {
        $this->checkToken('post');

        /** @var GeoupdateModel $model */
        $model = $this->getModel('Geoupdate');
        $id    = (int) $this->app->getInput()->getInt('id', 0);

        $more = $model->run(true, $id ?: null);

        $this->respond($more, $model);
    }

    /**
     * Emit a JSON response and terminate.
     *
     * @return  never
     *
     * @since   2.0.0
     */
    private function respond(bool $more, GeoupdateModel $model): never
    {
        $total   = max(0, $model->totalMembers);
        $done    = max(0, min($model->doneMembers, $total));
        $percent = $total > 0
            ? ($more ? (int) min(99, floor($done / $total * 100) + 1) : 100)
            : ($more ? 0 : 100);

        $this->app->setHeader('Content-Type', 'application/json');
        $this->app->sendHeaders();

        echo new JsonResponse([
            'state'   => $more ? 'running' : 'done',
            'total'   => $total,
            'done'    => $done,
            'percent' => $percent,
        ]);

        $this->app->close();
    }
}
