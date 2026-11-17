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

use CWM\Component\Cwmconnect\Administrator\Model\ReportsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel;

/**
 * Reports controller — generates CSV/KML/PDF exports of the directory.
 *
 * @since  2.0.0
 */
class ReportsController extends BaseController
{
    /**
     * Default view for the reports page.
     *
     * @var string
     * @since 2.0.0
     */
    protected $default_view = 'reports';

    /**
     * Display the reports page.
     *
     * @param   bool   $cachable   If true, the view output will be cached.
     * @param   array  $urlparams  An array of safe URL parameters.
     *
     * @return  static  This object to support chaining.
     *
     * @since   2.0.0
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $this->input->set('view', 'reports');

        return parent::display($cachable, $urlparams);
    }

    /**
     * Export task — runs a single export and short-circuits the response: the
     * report helper streams CSV, KML and missing-photo exports itself, and the
     * PDF is sent from here.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function export(): void
    {
        if (!Session::checkToken('get') && !Session::checkToken()) {
            throw new \Exception(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $report = (string) $this->input->get('report', 'directory', 'string');
        $type   = (string) $this->input->get('cdtype', 'csv', 'cmd');

        /** @var ReportsModel $model */
        $model = $this->getModel('Reports');
        $pdf   = $model->getExport($type, $report);

        if ($pdf !== null) {
            if ((bool) $this->input->getInt('include_hidden', 0)) {
                $this->logHiddenPrintOverride();
            }

            $this->sendPdf($pdf);
        }
    }

    /**
     * AJAX endpoint: build the directory PDF and send it back. Lets the
     * reports page show a "building…" spinner and then a download link,
     * instead of a frozen full-page navigation while mpdf renders hundreds
     * of members. A failure is a JSON message.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function generatepdf(): void
    {
        if (!Session::checkToken()) {
            $this->sendJson(new JsonResponse(new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403)), 403);
        }

        $report = (string) $this->input->get('report', 'directory', 'string');

        try {
            /** @var ReportsModel $model */
            $model = $this->getModel('Reports');
            $pdf   = $model->getExport('pdf', $report);

            if ($pdf === null) {
                throw new \RuntimeException(Text::_('COM_CWMCONNECT_REPORTS_PDF_FAILED'));
            }
        } catch (\Throwable $e) {
            $this->sendJson(new JsonResponse(null, $e->getMessage(), true));

            return;
        }

        if ((bool) $this->input->getInt('include_hidden', 0)) {
            $this->logHiddenPrintOverride();
        }

        $this->sendPdf($pdf);
    }

    /**
     * Send a directory PDF to the admin who asked for it, and end the request.
     * It holds every member's details, so it is never saved anywhere another
     * request could fetch it from: it exists only in this response.
     *
     * @param   array{content: string, name: string, count: int}  $pdf  From ReportsModel::getExport().
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function sendPdf(array $pdf): void
    {
        // Straight to the browser, not through the component's output buffer.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $this->app->setHeader('Content-Type', 'application/pdf', true);
        $this->app->setHeader('Content-Disposition', 'inline; filename="' . $pdf['name'] . '"', true);
        $this->app->setHeader('Content-Length', (string) \strlen($pdf['content']), true);
        $this->app->setHeader('Cache-Control', 'no-store, private', true);
        $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $this->app->setHeader('X-Cwmconnect-Members', (string) $pdf['count'], true);
        $this->app->sendHeaders();

        echo $pdf['content'];

        $this->app->close();
    }

    /**
     * Stream a JsonResponse envelope and end the request.
     *
     * @param   JsonResponse  $response    The response envelope.
     * @param   int           $httpStatus  HTTP status code to advertise.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function sendJson(JsonResponse $response, int $httpStatus = 200): void
    {
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->setHeader('status', (string) $httpStatus, true);
        $this->app->sendHeaders();

        echo $response;

        $this->app->close();
    }

    /**
     * Log to com_actionlogs when an admin generates a PDF that includes
     * hidden members (spec decision #17).
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function logHiddenPrintOverride(): void
    {
        try {
            $factory = Factory::getApplication()->bootComponent('com_actionlogs')->getMVCFactory();

            /** @var ActionlogModel $model */
            $model = $factory->createModel('Actionlog', 'Administrator');
            $model->addLog(
                [['action' => 'print_with_hidden_members']],
                'COM_CWMCONNECT_ACTIONLOG_PRINT_HIDDEN',
                'com_cwmconnect.reports',
            );
        } catch (\Throwable) {
        }
    }
}
