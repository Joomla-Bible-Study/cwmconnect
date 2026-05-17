<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Pcmappings;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Model\PcMappingsModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Phase D: list view for PC ↔ Joomla field mappings.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    public ?Form $filterForm = null;

    public ?array $activeFilters = null;

    protected ?array $items = null;

    protected ?object $pagination = null;

    protected mixed $state = null;

    protected mixed $canDo = null;

    #[\Override]
    public function display($tpl = null): void
    {
        /** @var PcMappingsModel $model */
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

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        $canDo   = $this->canDo;
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_CWMCONNECT_MANAGER_PCMAPPINGS'), 'cwmconnect');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('pcmapping.add');
        }

        if ($canDo->get('core.edit')) {
            $toolbar->edit('pcmapping.edit')->listCheck(true);
        }

        if ($canDo->get('core.delete')) {
            $toolbar->delete('pcmappings.delete', 'JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin', 'com_cwmconnect') || $this->getCurrentUser()->authorise('core.options', 'com_cwmconnect')) {
            ToolbarHelper::preferences('com_cwmconnect');
        }
    }
}
