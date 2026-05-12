<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Site\View\Directory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Stub PDF view.
 *
 * The legacy site/views/directory/view.pdf.php required the vendored mPDF
 * bundle that was dropped in PR #96. Reimplementing on top of a composer-
 * managed mPDF (or an alternative renderer) is its own deferred task —
 * tracked alongside Administrator\Helper\ReportbuildHelper::getPdf().
 *
 * Returns a 503 with a clear message so a request that lands here doesn't
 * silently render a blank page.
 *
 * @since  2.0.0
 */
class PdfView extends BaseHtmlView
{
    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        throw new \Exception(Text::_('COM_CHURCHDIRECTORY_PDF_EXPORT_NOT_IMPLEMENTED'), 503);
    }
}
