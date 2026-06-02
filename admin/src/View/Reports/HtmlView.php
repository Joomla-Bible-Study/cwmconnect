<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Reports;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\CanDo;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Reports landing view — exposes the export-trigger buttons.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var \Joomla\Registry\Registry|null */
    protected mixed $state = null;

    /** @var CanDo|null */
    protected ?CanDo $canDo = null;

    /**
     * Display the view.
     *
     * @param   string|null  $tpl  Template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $this->state = $this->getModel()->getState();
        $this->canDo = ContentHelper::getActions('com_cwmconnect');

        $this->addToolbar();
        $this->registerAssets();

        parent::display($tpl);
    }

    /**
     * Load the reports admin script and publish the endpoint URL, CSRF token,
     * and translated UI strings the AJAX PDF-build button reads.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function registerAssets(): void
    {
        $document = Factory::getApplication()->getDocument();
        $wa       = $document->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_cwmconnect');
        $wa->useScript('com_cwmconnect.admin-reports');

        $document->addScriptOptions('com_cwmconnect.reports', [
            'csrfToken'   => Session::getFormToken(),
            'generateUrl' => Route::_('index.php?option=com_cwmconnect&task=reports.generatepdf', false),
            'i18n'        => [
                'building'     => Text::_('COM_CWMCONNECT_REPORTS_PDF_BUILDING'),
                'download'     => Text::_('COM_CWMCONNECT_REPORTS_DOWNLOAD_PDF'),
                'ready'        => Text::_('COM_CWMCONNECT_REPORTS_PDF_READY'),
                'unknownError' => Text::_('COM_CWMCONNECT_REPORTS_PDF_UNKNOWN_ERROR'),
            ],
        ]);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @throws \Exception
     * @since 2.0.0
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CWMCONNECT_MANAGER_REPORTS'), 'checkbox');

        if ($this->canDo && $this->canDo->get('core.admin')) {
            ToolbarHelper::divider();
            ToolbarHelper::preferences('com_cwmconnect');
            ToolbarHelper::divider();
        }

        ToolbarHelper::help('churchdirectory', true);
    }
}
