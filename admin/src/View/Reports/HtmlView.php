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

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\CanDo;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
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

        parent::display($tpl);
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
        ToolbarHelper::title(Text::_('COM_CHURCHDIRECTORY_MANAGER_REPORTS'), 'checkbox');

        if ($this->canDo && $this->canDo->get('core.admin')) {
            ToolbarHelper::divider();
            ToolbarHelper::preferences('com_cwmconnect');
            ToolbarHelper::divider();
        }

        ToolbarHelper::help('churchdirectory', true);
    }
}
