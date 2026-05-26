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
use Joomla\CMS\Router\Route;
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
