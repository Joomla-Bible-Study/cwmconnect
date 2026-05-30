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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
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
     * Export task — runs a single export and short-circuits the response
     * (the report helper streams headers + body and exits).
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
        $model->getExport($type, $report);

        if ($type === 'pdf') {
            $includeHidden = (bool) $this->input->getInt('include_hidden', 0);

            if ($includeHidden) {
                $this->logHiddenPrintOverride();
            }

            $pdfPath = $this->app->getUserState('com_cwmconnect.reports.pdf_path', '');
            $this->app->setUserState('com_cwmconnect.reports.pdf_path', null);

            if ($pdfPath !== '') {
                $this->app->enqueueMessage(Text::_('COM_CWMCONNECT_REPORTS_PDF_GENERATED'));
            } else {
                $this->app->enqueueMessage(Text::_('COM_CWMCONNECT_REPORTS_PDF_FAILED'), 'error');
            }

            $this->setRedirect(Route::_('index.php?option=com_cwmconnect&view=reports', false));
        }
    }

    /**
     * AJAX endpoint: build the directory PDF and return its download URL as
     * JSON. Lets the reports page show a "building…" spinner and then a
     * download link, instead of a frozen full-page navigation while mpdf
     * renders hundreds of members.
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
            $model->getExport('pdf', $report);

            $path  = (string) $this->app->getUserState('com_cwmconnect.reports.pdf_path', '');
            $count = (int) $this->app->getUserState('com_cwmconnect.reports.pdf_count', 0);
            $this->app->setUserState('com_cwmconnect.reports.pdf_path', null);
            $this->app->setUserState('com_cwmconnect.reports.pdf_count', null);

            if ($path === '') {
                throw new \RuntimeException(Text::_('COM_CWMCONNECT_REPORTS_PDF_FAILED'));
            }

            if ((bool) $this->input->getInt('include_hidden', 0)) {
                $this->logHiddenPrintOverride();
            }

            $response = new JsonResponse(
                ['url' => Uri::root() . $path, 'count' => $count],
                Text::_('COM_CWMCONNECT_REPORTS_PDF_GENERATED'),
                false,
            );
        } catch (\Throwable $e) {
            $response = new JsonResponse(null, $e->getMessage(), true);
        }

        $this->sendJson($response);
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
