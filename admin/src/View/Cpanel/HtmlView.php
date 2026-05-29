<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Cpanel;

use CWM\Component\Cwmconnect\Administrator\Helper\SchemaCheck;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\CanDo;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Control panel view.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Component manifest XML, used to display the version on the dashboard.
     *
     * @var    \SimpleXMLElement|null
     * @since  2.0.0
     */
    protected ?\SimpleXMLElement $xml = null;

    /**
     * Permission set for the current user, returned by ContentHelper::getActions()
     * in J5/6 (was stdClass in J3/J4).
     *
     * @var    CanDo|null
     * @since  2.0.0
     */
    protected ?CanDo $canDo = null;

    /**
     * Whether the component has a pending schema update. When true the
     * cpanel renders a banner linking to com_installer&view=database.
     *
     * @var    bool
     * @since  2.0.0
     */
    protected bool $schemaFindings = false;

    /**
     * Whether Planning Center sync is enabled in component params.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    protected bool $pcEnabled = false;

    /**
     * Display the view.
     *
     * @param   string|null  $tpl  The template file to parse.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $manifest = JPATH_ADMINISTRATOR . '/components/com_cwmconnect/cwmconnect.xml';

        if (is_file($manifest)) {
            $xml = simplexml_load_file($manifest);

            if ($xml instanceof \SimpleXMLElement) {
                $this->xml = $xml;
            }
        }

        $this->canDo          = ContentHelper::getActions('com_cwmconnect');
        $this->schemaFindings = SchemaCheck::hasFindings();

        $params          = ComponentHelper::getParams('com_cwmconnect');
        $this->pcEnabled = (bool) $params->get('pc_enabled', 0);

        $this->registerPcAssets();
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CWMCONNECT_MANAGER_CPANEL'), 'address contact');

        if ($this->canDo && $this->canDo->get('core.admin')) {
            ToolbarHelper::divider();
            ToolbarHelper::preferences('com_cwmconnect');
            ToolbarHelper::divider();
        }

        ToolbarHelper::help('cwmconnect', true);
    }

    /**
     * Register the Planning Center admin asset script and pass script
     * options the JS reads to discover endpoint URLs and the CSRF token.
     * Only loaded when PC is enabled — otherwise the Cpanel doesn't render
     * the PC card at all.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function registerPcAssets(): void
    {
        if (!$this->pcEnabled) {
            return;
        }

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_cwmconnect');
        $wa->useScript('com_cwmconnect.admin-pc-sync');

        $document = Factory::getApplication()->getDocument();
        $document->addScriptOptions('com_cwmconnect.pc', [
            'csrfToken'      => Session::getFormToken(),
            'syncUrl'        => Route::_('index.php?option=com_cwmconnect&task=cpanel.pcSync', false),
            'testUrl'        => Route::_('index.php?option=com_cwmconnect&task=cpanel.pcTestConnection', false),
            'progressUrl'    => Route::_('index.php?option=com_cwmconnect&task=cpanel.pcSyncProgress', false),
            'i18n'           => [
                'syncing'        => Text::_('COM_CWMCONNECT_PC_SYNCING'),
                'testing'        => Text::_('COM_CWMCONNECT_PC_TESTING'),
                'unknownError'   => Text::_('COM_CWMCONNECT_PC_UNKNOWN_ERROR'),
                'summary'        => Text::_('COM_CWMCONNECT_PC_SUMMARY'),
                'progressPage'   => Text::_('COM_CWMCONNECT_PC_PROGRESS_PAGE'),
                'progressSweep'  => Text::_('COM_CWMCONNECT_PC_PROGRESS_SWEEP'),
            ],
        ]);
    }
}
