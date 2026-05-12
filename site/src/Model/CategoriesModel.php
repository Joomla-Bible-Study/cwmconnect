<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Registry\Registry;

/**
 * Categories list model — returns the child categories of a given parent
 * (default: root), for rendering the category tree menu view.
 *
 * @since  2.0.0
 */
class CategoriesModel extends ListModel
{
    /** @var string Model context for state caching. */
    public $context = 'com_cwmconnect.categories';

    /** @var string Component extension scoped by this model. */
    protected $extension = 'com_cwmconnect';

    /** @var CategoryNode|null Parent category of the current view. */
    private ?CategoryNode $parent = null;

    /** @var array<int, CategoryNode>|false|null Cached children. */
    private $items = null;

    /**
     * Auto-populate state from the request — id selects the parent category.
     *
     * @since  2.0.0
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app = Factory::getApplication();

        $this->setState('filter.extension', $this->extension);
        $this->setState('filter.parentId', $app->getInput()->getInt('id'));
        $this->setState('filter.published', 1);
        $this->setState('filter.access', true);
        $this->setState('params', $app->getParams());
    }

    /** Compose a cache key that includes every filter we vary on. */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.extension');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.parentId');

        return parent::getStoreId($id);
    }

    /**
     * Load the child categories of the active parent. Honors menu-level
     * `show_cat_items_cat` and `show_empty_categories_cat` params so empty
     * branches can be hidden.
     *
     * @return  array<int, CategoryNode>|false
     *
     * @since   2.0.0
     */
    public function getItems()
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $active = Factory::getApplication()->getMenu()?->getActive();
        $params = new Registry();

        if ($active) {
            $params->loadString($active->params);
        }

        $options = [
            'countItems' => $params->get('show_cat_items_cat', 1)
                || !$params->get('show_empty_categories_cat', 0),
        ];

        $categories   = Categories::getInstance('Cwmconnect', $options);
        $this->parent = $categories->get($this->getState('filter.parentId', 'root'));

        $this->items = \is_object($this->parent) ? $this->parent->getChildren() : false;

        return $this->items;
    }

    /**
     * Return the parent CategoryNode (loading it via getItems() if needed).
     *
     * @since  2.0.0
     */
    public function getParent(): ?CategoryNode
    {
        if (!\is_object($this->parent)) {
            $this->getItems();
        }

        return $this->parent;
    }
}
