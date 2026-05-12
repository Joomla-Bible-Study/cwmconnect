<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\View\Cpanel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Control panel view.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var \SimpleXMLElement|null  Component manifest XML, used to display version on the dashboard.
     * @since 2.0.0
     */
    protected ?\SimpleXMLElement $xml = null;

    /**
     * @var \stdClass|null  Permission set for the current user.
     * @since 2.0.0
     */
    protected ?\stdClass $canDo = null;

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
        $manifest = JPATH_ADMINISTRATOR . '/components/com_churchdirectory/churchdirectory.xml';

        if (is_file($manifest)) {
            $xml = simplexml_load_file($manifest);

            if ($xml instanceof \SimpleXMLElement) {
                $this->xml = $xml;
            }
        }

        $this->canDo = ContentHelper::getActions('com_churchdirectory');

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
        ToolbarHelper::title(Text::_('COM_CHURCHDIRECTORY_MANAGER_CPANEL'), 'address contact');

        if ($this->canDo && $this->canDo->get('core.admin')) {
            ToolbarHelper::divider();
            ToolbarHelper::preferences('com_churchdirectory');
            ToolbarHelper::divider();
        }

        ToolbarHelper::help('churchdirectory', true);
    }
}
