<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\View\Category;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Site\Helper\RenderHelper;
use CWM\Component\Connect\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Mail\MailHelper;
use Joomla\CMS\MVC\View\CategoryView;
use Joomla\CMS\Router\Route;

/**
 * Site category view — renders one category of members.
 *
 * @since  2.0.0
 */
class HtmlView extends CategoryView
{
    /** @var string Component extension key. */
    protected $extension = 'com_cwmconnect';

    /** @var string Default page-title language key. */
    protected $defaultPageTitle = 'COM_CWMCONNECT_DEFAULT_PAGE_TITLE';

    /** @var string Link target view for individual list items. */
    protected $viewName = 'member';

    /** @var bool Run onContent* plugins on items. */
    protected $runPlugins = true;

    /** @var RenderHelper */
    public RenderHelper $renderHelper;

    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        parent::commonCategoryDisplay();

        foreach ($this->items as $item) {
            $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : (string) $item->id;

            $temp         = $item->params;
            $item->params = clone $this->params;
            $item->params->merge($temp);

            if ((string) $item->params->get('show_email_headings', 0) === '1') {
                $item->email_to = trim((string) ($item->email_to ?? ''));
                $item->email_to = ($item->email_to !== '' && MailHelper::isEmailAddress($item->email_to))
                    ? HTMLHelper::_('email.cloak', $item->email_to)
                    : '';
            }
        }

        $this->renderHelper = new RenderHelper();

        parent::display($tpl);
    }

    /**
     * Build the breadcrumb pathway up to (but not including) the active menu item.
     *
     * @since  2.0.0
     */
    protected function prepareDocument(): void
    {
        parent::prepareDocument();

        $menu = $this->menu;
        $id   = (int) ($menu->query['id'] ?? 0);

        if (
            $menu
            && (
                ($menu->query['option'] ?? '') !== $this->extension
                || ($menu->query['view'] ?? '') === $this->viewName
                || $id !== (int) $this->category->id
            )
        ) {
            $path     = [['title' => $this->category->title, 'link' => '']];
            $category = $this->category->getParent();

            while (
                $category
                && (($menu->query['option'] ?? '') !== $this->extension || ($menu->query['view'] ?? '') === $this->viewName || $id !== (int) $category->id)
                && $category->id > 1
            ) {
                $path[]   = [
                    'title' => $category->title,
                    'link'  => Route::_(RouteHelper::getCategoryRoute($category->id)),
                ];
                $category = $category->getParent();
            }

            foreach (array_reverse($path) as $item) {
                $this->pathway->addItem($item['title'], $item['link']);
            }
        }

        parent::addFeed();
    }
}
