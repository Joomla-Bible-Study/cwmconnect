<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\View\Featured;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Site\Helper\RenderHelper;
use CWM\Component\Connect\Site\Model\FeaturedModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Registry\Registry;

/**
 * HTML view for the featured-members list.
 *
 * @since  2.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var Registry|null */
    protected mixed $state = null;

    /** @var array<int, object> */
    protected array $items = [];

    /** @var Pagination|null */
    protected ?Pagination $pagination = null;

    /** @var Registry */
    protected Registry $params;

    /** @var string Sanitized page-class suffix. */
    protected string $pageclass_sfx = '';

    /** @var RenderHelper */
    protected RenderHelper $renderHelper;

    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        /** @var FeaturedModel $model */
        $model            = $this->getModel();
        $this->state      = $model->getState();
        $this->items      = $model->getItems() ?: [];
        $this->pagination = $model->getPagination();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $params = ComponentHelper::getParams('com_cwmconnect');

        foreach ($this->items as $item) {
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : (string) $item->id;

            $temp = new Registry();
            $temp->loadString((string) $item->params);
            $merged = clone $params;
            $merged->merge($temp);
            $item->params = $merged;

            if ((int) $item->params->get('show_email', 0) === 1) {
                $item->email_to = trim((string) ($item->email_to ?? ''));
                $item->email_to = ($item->email_to !== '' && MailHelper::isEmailAddress($item->email_to))
                    ? HTMLHelper::_('email.cloak', $item->email_to)
                    : '';
            }
        }

        $this->params        = $params;
        $this->pageclass_sfx = htmlspecialchars((string) $params->get('pageclass_sfx', ''));
        $this->renderHelper  = new RenderHelper();

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Set page title and meta based on active menu / global config.
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

        if ($this->params->get('menu-meta_description')) {
            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->getDocument()->setMetaData('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
        }
    }
}
