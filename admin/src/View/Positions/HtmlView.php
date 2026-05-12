<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Administrator\View\Positions;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Administrator\Model\PositionsModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Positions.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var Form|null
     * @since 2.0.0
     */
    public ?Form $filterForm = null;

    /**
     * @var array|null
     * @since 2.0.0
     */
    public ?array $activeFilters = null;

    /**
     * @var array|null
     * @since 2.0.0
     */
    protected ?array $items = null;

    /**
     * @var object|null
     * @since 2.0.0
     */
    protected ?object $pagination = null;

    /**
     * @var mixed
     * @since 2.0.0
     */
    protected mixed $state = null;

    /**
     * @var mixed
     * @since 2.0.0
     */
    protected mixed $canDo = null;

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
        /** @var PositionsModel $model */
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
        $this->canDo         = ContentHelper::getActions('com_cwmconnect');

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        // Pre-process the list to allow up/down ordering hints.
        if (\is_array($this->items)) {
            foreach ($this->items as &$item) {
                $item->order_up = true;
                $item->order_dn = true;
            }
            unset($item);
        }

        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        } else {
            // In modal layout, allow forced language to lock the language filter.
            $forcedLanguage = Factory::getApplication()->getInput()->get('forcedLanguage', '', 'CMD');

            if ($forcedLanguage && $this->filterForm) {
                $languageXml = new \SimpleXMLElement(
                    '<field name="language" type="hidden" default="' . $forcedLanguage . '" />'
                );
                $this->filterForm->setField($languageXml, 'filter', true);

                unset($this->activeFilters['language']);

                $this->filterForm->setFieldAttribute('category_id', 'language', '*,' . $forcedLanguage, 'filter');
            }
        }

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
        $user    = $this->getCurrentUser();
        $canDo   = $this->canDo;
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_CWMCONNECT_MANAGER_POSITIONS'), 'cwmconnect');

        if ($canDo->get('core.create') || \count($user->getAuthorisedCategories('com_cwmconnect', 'core.create')) > 0) {
            $toolbar->addNew('position.add');
        }

        if ($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
            $toolbar->edit('position.edit')->listCheck(true);
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);
            $childBar = $dropdown->getChildToolbar();

            $childBar->publish('positions.publish')->listCheck(true);
            $childBar->unpublish('positions.unpublish')->listCheck(true);
            $childBar->archive('positions.archive')->listCheck(true);
            $childBar->checkin('positions.checkin')->listCheck(true);

            if ((int) $this->state->get('filter.published') !== -2) {
                $childBar->trash('positions.trash')->listCheck(true);
            }
        }

        // Batch button.
        if (
            $user->authorise('core.create', 'com_cwmconnect')
            && $user->authorise('core.edit', 'com_cwmconnect')
            && $user->authorise('core.edit.state', 'com_cwmconnect')
        ) {
            $toolbar->popupButton('batch', 'JTOOLBAR_BATCH')
                ->popupType('inline')
                ->textHeader(Text::_('COM_CWMCONNECT_BATCH_OPTIONS_POSITION'))
                ->url('#joomla-dialog-batch')
                ->modalWidth('800px')
                ->modalHeight('fit-content')
                ->listCheck(true);
        }

        if ((int) $this->state->get('filter.published') === -2 && $canDo->get('core.delete')) {
            $toolbar->delete('positions.delete', 'JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin', 'com_cwmconnect') || $user->authorise('core.options', 'com_cwmconnect')) {
            ToolbarHelper::preferences('com_cwmconnect');
        }

        ToolbarHelper::help('cwmconnect_position', true);
    }

    /**
     * Returns an array of fields the table can be sorted by.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    protected function getSortFields(): array
    {
        return [
            'a.ordering' => Text::_('JGRID_HEADING_ORDERING'),
            'a.state'    => Text::_('JSTATUS'),
            'a.name'     => Text::_('JGLOBAL_TITLE'),
            'a.access'   => Text::_('JGRID_HEADING_ACCESS'),
            'a.language' => Text::_('JGRID_HEADING_LANGUAGE'),
            'a.id'       => Text::_('JGRID_HEADING_ID'),
        ];
    }
}
