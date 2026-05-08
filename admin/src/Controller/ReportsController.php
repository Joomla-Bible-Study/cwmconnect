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

use CWM\Component\Churchdirectory\Administrator\Model\ReportsModel;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

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
        Session::checkToken('get') || Session::checkToken();

        $report = (string) $this->input->get('report', 'directory', 'string');
        $type   = (string) $this->input->get('cdtype', 'csv', 'cmd');

        /** @var ReportsModel $model */
        $model = $this->getModel('Reports');
        $model->getExport($type, $report);
    }
}
