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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * Category item-list model — loads the members in one category, plus the
 * category record, parent, children, and adjacent siblings for navigation.
 *
 * @since  2.0.0
 */
class CategoryModel extends ListModel
{
    /** @var CategoryNode|null Current category. */
    protected ?CategoryNode $item = null;

    /** @var array<int, CategoryNode>|false Child categories. */
    protected mixed $children = false;

    /** @var CategoryNode|false Parent category. */
    protected mixed $parent = false;

    /** @var CategoryNode|null Right sibling. */
    protected ?CategoryNode $rightsibling = null;

    /** @var CategoryNode|null Left sibling. */
    protected ?CategoryNode $leftsibling = null;

    /** @var TagsHelper|null Per-item tag lookup helper. */
    protected ?TagsHelper $tags = null;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'con_position', 'a.con_position',
                'suburb', 'a.suburb',
                'state', 'a.state',
                'country', 'a.country',
                'ordering', 'a.ordering',
                'sortname',
                'sortname1', 'a.sortname1',
                'sortname2', 'a.sortname2',
                'sortname3', 'a.sortname3',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Load members + decode params + attach tags.
     *
     * @return  array<int, object>|false
     *
     * @since   2.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (!\is_array($items)) {
            return $items;
        }

        $this->tags ??= new TagsHelper();

        foreach ($items as $item) {
            $item->params = new Registry($item->params);
            $this->tags->getItemTags('com_cwmconnect.member', $item->id);
        }

        return $items;
    }

    /**
     * Build the member list SQL — supports sortname (3-column ordering) and
     * the standard list.ordering / list.direction state.
     *
     * @since   2.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $user   = Factory::getApplication()->getIdentity();
        $groups = implode(',', $user ? $user->getAuthorisedViewLevels() : [1]);

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $caseSlug    = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('a.id'), 'a.alias'], ':')
            . ' ELSE ' . $query->castAsChar('a.id') . ' END as slug';
        $caseCatslug = ' CASE WHEN ' . $query->charLength('c.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('c.id'), 'c.alias'], ':')
            . ' ELSE ' . $query->castAsChar('c.id') . ' END as catslug';

        $query->select($this->getState('list.select', 'a.*') . ', ' . $caseSlug . ', ' . $caseCatslug)
            ->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author")
            ->select('ua.email AS author_email')
            ->select('ca.title AS category_title, ca.params AS category_params, ca.description AS category_description')
            ->select('ca.alias AS category_alias, ca.access AS category_access')
            ->from($db->quoteName('#__cwmconnect_details', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ua.id = a.created_by')
            ->join('LEFT', $db->quoteName('#__users', 'uam') . ' ON uam.id = a.modified_by')
            ->join('INNER', $db->quoteName('#__categories', 'ca') . ' ON ca.id = a.catid')
            ->where('a.access IN (' . $groups . ')');

        if ($categoryId = $this->getState('category.id')) {
            $query->where('a.catid = ' . (int) $categoryId)
                ->where('c.access IN (' . $groups . ')');
        }

        $state = $this->getState('filter.published');

        if (is_numeric($state)) {
            $query->where('a.published = ' . (int) $state);
        }

        if ($this->getState('filter.publish_date')) {
            $nullDate = $db->quote($db->getNullDate());
            $nowDate  = $db->quote(Factory::getDate()->toSql());
            $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
                ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
        }

        if ($search = $this->getState('list.filter')) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(a.name LIKE ' . $search . ')');
        }

        if ($this->getState('filter.language')) {
            $query->where('a.language IN ('
                . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ', '
                . $db->quote('*') . ')');
        }

        $listDir = $db->escape((string) $this->getState('list.direction', 'ASC'));

        if ($this->getState('list.ordering') === 'sortname') {
            $query->order($db->escape('a.sortname1') . ' ' . $listDir)
                ->order($db->escape('a.sortname2') . ' ' . $listDir)
                ->order($db->escape('a.sortname3') . ' ' . $listDir);
        } else {
            $col = $db->escape((string) $this->getState('list.ordering', 'a.ordering'));
            $query->order($col . ' ' . $listDir);
        }

        return $query;
    }

    /**
     * State pulled from request + active menu params, with the standard
     * non-editor "published only" safety net.
     *
     * @since   2.0.0
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_cwmconnect');
        $format = $app->getInput()->getWord('format');

        if (!$app->getInput()->getInt('print')) {
            $limit = $format === 'feed'
                ? $app->get('feed_limit')
                : $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
            $this->setState('list.limit', $limit);
            $this->setState('list.start', $app->getInput()->getInt('limitstart', 0));
        }

        $this->setState('list.filter', $app->getInput()->getString('filter-search', ''));

        $menuParams = new Registry();

        if ($menu = $app->getMenu()?->getActive()) {
            $menuParams->loadString($menu->params);
        }

        $merged = clone $params;
        $merged->merge($menuParams);
        $orderCol = $app->getInput()->get('filter_order', $merged->get('initial_sort', 'ordering'));

        if (!\in_array($orderCol, $this->filter_fields, true)) {
            $orderCol = 'ordering';
        }

        $this->setState('list.ordering', $orderCol);

        $listOrder = strtoupper((string) $app->getInput()->get('filter_order_Dir', 'ASC'));

        if (!\in_array($listOrder, ['ASC', 'DESC', ''], true)) {
            $listOrder = 'ASC';
        }

        $this->setState('list.direction', $listOrder);
        $this->setState('category.id', $app->getInput()->getInt('id', 0));

        $user = $app->getIdentity();

        if ($user && !$user->authorise('core.edit.state', 'com_cwmconnect') && !$user->authorise('core.edit', 'com_cwmconnect')) {
            $this->setState('filter.published', 1);
            $this->setState('filter.publish_date', true);
        }

        $this->setState('filter.language', $app->getLanguageFilter());
        $this->setState('params', $params);
    }

    /**
     * Load the current category record and walk parent/children/sibling links.
     *
     * @since   2.0.0
     */
    public function getCategory(): ?CategoryNode
    {
        if (\is_object($this->item)) {
            return $this->item;
        }

        $menuParams = new Registry();

        if ($active = Factory::getApplication()->getMenu()?->getActive()) {
            $menuParams->loadString($active->params);
        }

        $options = [
            'countItems' => $menuParams->get('show_cat_items', 1) || $menuParams->get('show_empty_categories', 0),
        ];

        $categories = Categories::getInstance('Cwmconnect', $options);
        $this->item = $categories->get($this->getState('category.id', 'root'));

        if (\is_object($this->item)) {
            $this->children     = $this->item->getChildren();
            $this->parent       = $this->item->getParent() ?: false;
            $this->rightsibling = $this->item->getSibling();
            $this->leftsibling  = $this->item->getSibling(false);
        }

        return $this->item;
    }

    public function getParent(): CategoryNode|false
    {
        if (!\is_object($this->item)) {
            $this->getCategory();
        }

        return $this->parent;
    }

    public function &getLeftSibling(): ?CategoryNode
    {
        if (!\is_object($this->item)) {
            $this->getCategory();
        }

        return $this->leftsibling;
    }

    public function &getRightSibling(): ?CategoryNode
    {
        if (!\is_object($this->item)) {
            $this->getCategory();
        }

        return $this->rightsibling;
    }

    /**
     * @return  array<int, CategoryNode>|false
     */
    public function &getChildren()
    {
        if (!\is_object($this->item)) {
            $this->getCategory();
        }

        return $this->children;
    }

    /**
     * Bump the category hit counter — only when the `hitcount` request param
     * is truthy (templates suppress the bump when caching/feeds).
     *
     * @since   2.0.0
     */
    public function hit(int $pk = 0): bool
    {
        $input    = Factory::getApplication()->getInput();
        $hitcount = $input->getInt('hitcount', 1);

        if (!$hitcount) {
            return true;
        }

        $pk    = $pk ?: (int) $this->getState('category.id');
        $table = Table::getInstance('Category', 'JTable');
        $table->load($pk);
        $table->hit($pk);

        return true;
    }
}
