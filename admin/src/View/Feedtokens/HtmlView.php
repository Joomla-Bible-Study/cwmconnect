<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Feedtokens;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * List view for feed token management.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /** @since __DEPLOY_VERSION__ */
    public array $items = [];

    /** @since __DEPLOY_VERSION__ */
    public ?Pagination $pagination = null;

    /** @since __DEPLOY_VERSION__ */
    public mixed $state = null;

    /** @since __DEPLOY_VERSION__ */
    public ?object $filterForm = null;

    /** @since __DEPLOY_VERSION__ */
    public array $activeFilters = [];

    /**
     * @param   string|null  $tpl  Template name.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $model = $this->getModel();

        $this->items         = $model->getItems() ?: [];
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_CWMCONNECT_MANAGER_FEEDTOKENS'), 'key');

        $canDo = ContentHelper::getActions('com_cwmconnect');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('feedtoken.add');
        }

        $toolbar = Toolbar::getInstance();

        if ($canDo->get('core.edit.state')) {
            $toolbar->standardButton('unpublish', 'COM_CWMCONNECT_FEEDTOKEN_BTN_REVOKE', 'feedtokens.revoke')
                ->icon('icon-ban-circle')
                ->listCheck(true);
        }

        if ($canDo->get('core.delete')) {
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'feedtokens.delete');
        }
    }
}
