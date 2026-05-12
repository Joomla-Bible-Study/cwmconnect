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

use CWM\Component\Connect\Site\Helper\RouteHelper;
use Joomla\CMS\Document\Feed\FeedItem;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

/**
 * RSS feed view for a single directory category.
 *
 * @since  2.0.0
 */
class FeedView extends BaseHtmlView
{
    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app  = Factory::getApplication();
        $doc  = $app->getDocument();
        $model = $this->getModel();

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $feedEmail = $app->get('feed_email', 'author');
        $siteEmail = $app->get('mailfrom');

        $app->getInput()->set('limit', $app->get('feed_limit'));

        $category = $model->getCategory();
        $rows     = $model->getItems();

        $doc->link = Route::_(RouteHelper::getCategoryRoute($category->id));

        foreach ($rows as $row) {
            $title = html_entity_decode($this->escape($row->name), ENT_COMPAT, 'UTF-8');
            $row->slug = $row->alias ? ($row->id . ':' . $row->alias) : (string) $row->id;
            $link = Route::_(RouteHelper::getMemberRoute($row->slug, $row->catid));
            $date = $row->created ? date('r', strtotime($row->created)) : '';
            $author = $row->created_by_alias ?: $row->author;

            $item              = new FeedItem();
            $item->title       = $title;
            $item->link        = $link;
            $item->description = $row->introtext ?? '';
            $item->date        = $date;
            $item->category    = $category->title;
            $item->author      = $author;
            $item->authorEmail = $feedEmail === 'site' ? $siteEmail : ($row->author_email ?? '');

            $doc->addItem($item);
        }
    }
}
