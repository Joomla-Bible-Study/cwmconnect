<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\View\Geostatus;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Model\GeostatusModel;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\CanDo;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Geocoding-status list view.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var array<int, object> */
    protected array $items = [];

    /** @var Pagination|null */
    protected ?Pagination $pagination = null;

    /** @var \Joomla\Registry\Registry|null */
    protected mixed $state = null;

    /** @var \Joomla\CMS\Form\Form|null */
    public mixed $filterForm = null;

    /** @var array<string, mixed> */
    public array $activeFilters = [];

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
        /** @var GeostatusModel $model */
        $model               = $this->getModel();
        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
        $this->canDo         = ContentHelper::getActions('com_cwmconnect');

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->getDocument()
            ->getWebAssetManager()
            ->useScript('com_cwmconnect.geoupdate');

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
        ToolbarHelper::title(Text::_('COM_CWMCONNECT_TITLE_GEOUPDATE_STATUS'), 'geo');

        $toolbar = $this->getDocument()->getToolbar();

        // The worker runs entirely client-side via media/com_cwmconnect/js/geoupdate.js,
        // which posts to task=geoupdate.start/slice. The button just opens the modal
        // already rendered in tmpl/geostatus/default.php.
        $toolbar->customButton('geoupdate')
            ->buttonClass('btn btn-primary')
            ->text('COM_CWMCONNECT_GEOUPDATE')
            ->icon('icon-refresh')
            ->attributes([
                'type'           => 'button',
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#geoupdateModal',
            ]);

        if ($this->canDo && $this->canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_cwmconnect');
        }

        ToolbarHelper::help('cwmconnect_geoupdate', true);
    }

    /**
     * Returns an array of fields the table can be sorted by.
     *
     * @return array<string, string>
     *
     * @since 2.0.0
     */
    protected function getSortFields(): array
    {
        return [
            'a.ordering'     => Text::_('JGRID_HEADING_ORDERING'),
            'a.published'    => Text::_('JSTATUS'),
            'a.name'         => Text::_('JGLOBAL_TITLE'),
            'category_title' => Text::_('JCATEGORY'),
            'ul.name'        => Text::_('COM_CHURCHDIRECTORY_FIELD_LINKED_USER_LABEL'),
            'a.featured'     => Text::_('JFEATURED'),
            'a.access'       => Text::_('JGRID_HEADING_ACCESS'),
            'a.language'     => Text::_('JGRID_HEADING_LANGUAGE'),
            'a.id'           => Text::_('JGRID_HEADING_ID'),
        ];
    }
}
