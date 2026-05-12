<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\View\Directory;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Site\Helper\RenderHelper;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;

/**
 * HTML view for the full directory list. Dispatches between three layouts —
 * "home" (default landing), "search" (search results) and the rendered
 * directory templates — based on the request layout.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var Registry */
    protected Registry $params;

    /** @var CategoryNode|null */
    protected ?CategoryNode $category = null;

    /** @var \Joomla\Registry\Registry|null */
    protected mixed $state = null;

    /** @var array<int, object>|false */
    protected mixed $items = false;

    /** @var Pagination|null */
    protected ?Pagination $pagination = null;

    /** @var RenderHelper */
    protected RenderHelper $renderHelper;

    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $model = $this->getModel();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->params   = ComponentHelper::getParams('com_cwmconnect');
        $this->category = $model->getCategory();
        $this->state    = $model->getState();
        $this->pagination = $model->getPagination();

        $this->prepareDocument();

        $layout = Factory::getApplication()->getInput()->get('layout', 'home');

        if ($layout === 'search') {
            // Search-form defaults — same overrides as the home view.
            $defaults = new Registry();
            $defaults->set('opensearch', '1');
            $defaults->set('size-lbl', '12');
            $defaults->set('show_button', '1');
            $defaults->set('button_pos', 'right');
            $this->params->merge($defaults);

            $this->renderHelper = new RenderHelper();
            $this->items        = $model->getSearch();
        } else {
            $this->renderHelper = new RenderHelper();
            $this->items        = $model->getItems();
        }

        $this->setLayout($layout);

        parent::display();
    }

    /**
     * Set page title and meta from active menu / category.
     *
     * @since  2.0.0
     */
    protected function prepareDocument(): void
    {
        $app  = Factory::getApplication();
        $menu = $app->getMenu()?->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_CWMCONNECT_DEFAULT_PAGE_TITLE'));
        }

        $title = (string) $this->params->get('page_title', '');

        if ($title === '') {
            $title = $app->get('sitename');
        } elseif ((int) $app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ((int) $app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);

        $metadesc = $this->category?->metadesc ?: $this->params->get('menu-meta_description');

        if ($metadesc) {
            $this->getDocument()->setDescription($metadesc);
        }

        $metakey = $this->category?->metakey ?: $this->params->get('menu-meta_keywords');

        if ($metakey) {
            $this->getDocument()->setMetaData('keywords', $metakey);
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }
    }
}
