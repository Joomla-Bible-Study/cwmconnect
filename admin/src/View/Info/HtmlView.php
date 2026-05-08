<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\View\Info;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Info ("about this component") view.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
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
        ToolbarHelper::title(Text::_('COM_CHURCHDIRECTORY_MANAGER_INFO'), 'info');
        ToolbarHelper::help('churchdirectory', true);
    }
}
