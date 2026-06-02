<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Reconcile;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Model\ReconcileModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Reconcile tool view — hand-entered (non-PC) member rows.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var array<int, object>|null
     * @since __DEPLOY_VERSION__
     */
    protected ?array $items = null;

    /**
     * @var object|null
     * @since __DEPLOY_VERSION__
     */
    protected ?object $pagination = null;

    /**
     * @var array<int, string>
     * @since __DEPLOY_VERSION__
     */
    protected array $syncedOptions = [];

    /**
     * Render the view.
     *
     * @param   string|null  $tpl  The template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var ReconcileModel $model */
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->syncedOptions = $model->getSyncedOptions();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Configure the toolbar.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CWMCONNECT_RECONCILE_TITLE'), 'cwmconnect');

        $toolbar = Toolbar::getInstance('toolbar');
        $toolbar->link('JTOOLBAR_BACK', 'index.php?option=com_cwmconnect&view=cpanel')->icon('icon-arrow-left');

        if ($this->getCurrentUser()->authorise('core.admin', 'com_cwmconnect')) {
            ToolbarHelper::preferences('com_cwmconnect');
        }
    }
}
