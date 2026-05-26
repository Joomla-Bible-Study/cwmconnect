<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Members;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Model\MembersModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Phase I: PDF export of the filtered member directory.
 *
 * Loads the same data as the HTML list view (via MembersModel), renders a
 * print-oriented HTML template, passes it through mpdf, and streams the
 * result as a downloadable PDF. Pagination is removed so the PDF contains
 * every matching row.
 *
 * @since  __DEPLOY_VERSION__
 */
class PdfView extends BaseHtmlView
{
    /**
     * @var    list<object>
     * @since  __DEPLOY_VERSION__
     */
    public array $items = [];

    /**
     * Render the PDF and stream it to the browser.
     *
     * @param   string|null  $tpl  Template name (unused — always renders default_pdf).
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var MembersModel $model */
        $model = $this->getModel();

        $model->setState('list.start', 0);
        $model->setState('list.limit', 0);

        $this->items = $model->getItems() ?: [];

        if ($this->items === []) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PDF_ERROR_NO_MEMBERS'), 404);
        }

        ob_start();
        $this->setLayout('default_pdf');
        parent::display();
        $html = ob_get_clean();

        $app = Factory::getApplication();

        $autoload = JPATH_LIBRARIES . '/mpdf/vendor/autoload.php';

        if (!is_file($autoload)) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PDF_ERROR_LIB_MISSING'), 500);
        }

        require_once $autoload;

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'Letter',
                'margin_left'   => 15,
                'margin_right'  => 15,
                'margin_top'    => 16,
                'margin_bottom' => 16,
                'tempDir'       => $app->get('tmp_path', sys_get_temp_dir()),
            ]);

            $mpdf->SetTitle(Text::_('COM_CWMCONNECT_PDF_TITLE'));
            $mpdf->SetAuthor(Text::_('COM_CWMCONNECT'));
            $mpdf->WriteHTML($html);
        } catch (\Mpdf\MpdfException $e) {
            throw new \RuntimeException(Text::sprintf('COM_CWMCONNECT_PDF_ERROR_RENDER', $e->getMessage()), 500);
        }

        $filename = 'church-directory-' . date('Y-m-d') . '.pdf';

        $app->setHeader('Content-Type', 'application/pdf', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Cache-Control', 'private, max-age=0, must-revalidate', true);
        $app->sendHeaders();

        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);

        $app->close();
    }
}
