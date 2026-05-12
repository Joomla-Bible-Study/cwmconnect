<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Directory list model — the full member directory with category, family unit,
 * and KML joins. Supports family/individual layout switching, prefix-keyed
 * search (`id:`, `suburb:`, `address:`, `zip:`), date filtering, and the
 * sortname three-column ordering scheme.
 *
 * @since  2.0.0
 */
class DirectoryModel extends ListModel
{
    /** @var CategoryNode|null Active category record. */
    protected ?CategoryNode $item = null;

    /** @var array<int, CategoryNode>|false Child categories. */
    protected mixed $children = false;

    /** @var CategoryNode|false Parent category. */
    protected mixed $parent = false;

    /** @var CategoryNode|null */
    protected ?CategoryNode $rightsibling = null;

    /** @var CategoryNode|null */
    protected ?CategoryNode $leftsibling = null;

    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'lname', 'a.lname',
                'suburb', 'a.suburb',
                'state', 'a.state',
                'country', 'a.country',
                'ordering', 'a.ordering',
                'sortname1', 'a.sortname1',
                'sortname2', 'a.sortname2',
                'sortname3', 'a.sortname3',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Decode every Registry-encoded column on each item.
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

        foreach ($items as $item) {
            foreach (['kml_params', 'category_params', 'params', 'attribs'] as $col) {
                $reg = new Registry();
                $reg->loadString((string) $item->$col);
                $item->$col = $reg;
            }
        }

        return $items;
    }

    /**
     * Massive multi-join member list, with prefix-keyed search and date
     * filtering. Mirrors the legacy J3 query precisely; only the API surface
     * differs (Factory, getDatabase(), getInput()).
     *
     * @return  QueryInterface
     *
     * @since   2.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $user   = Factory::getApplication()->getIdentity();
        $db     = $this->getDatabase();
        $query  = $db->getQuery(true);

        $caseSlug    = ' CASE WHEN ' . $query->charLength('a.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('a.id'), 'a.alias'], ':')
            . ' ELSE ' . $query->castAsChar('a.id') . ' END as slug';
        $caseCatslug = ' CASE WHEN ' . $query->charLength('c.alias', '!=', '0')
            . ' THEN ' . $query->concatenate([$query->castAsChar('c.id'), 'c.alias'], ':')
            . ' ELSE ' . $query->castAsChar('c.id') . ' END as catslug';

        $query->select($this->getState('item.select', 'a.*') . ', ' . $caseSlug . ', ' . $caseCatslug)
            ->from($db->quoteName('#__churchdirectory_details', 'a'))
            ->select('k.name AS kml_name, k.style AS kml_style, k.params AS kml_params, k.alias AS kml_alias,'
                . ' k.access AS kml_access, k.lat AS kml_lat, k.lng AS kml_lng')
            ->join('LEFT', $db->quoteName('#__churchdirectory_kml', 'k') . ' ON k.id = a.kmlid')
            ->select('fu.id AS funit_id, fu.name AS funit_name, fu.image as funit_image, fu.access as funit_access')
            ->join('LEFT', $db->quoteName('#__churchdirectory_familyunit', 'fu') . ' ON fu.id = a.funitid')
            ->select('c.title AS category_title, c.params AS category_params, c.alias AS category_alias,'
                . ' c.description AS category_description, c.access AS category_access')
            ->join('INNER', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author")
            ->select('ua.email AS author_email')
            ->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ua.id = a.created_by')
            ->join('LEFT', $db->quoteName('#__users', 'uam') . ' ON uam.id = a.modified_by')
            ->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route,'
                . ' parent.alias as parent_alias')
            ->join('LEFT', $db->quoteName('#__categories', 'parent') . ' ON parent.id = c.parent_id')
            ->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');

        $subquery  = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
        $subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
        $subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

        if ($this->getState('filter.published') == 2) {
            $subquery       .= ' AND parent.published = 2 GROUP BY cat.id';
            $publishedWhere = 'CASE WHEN badcats.id is null THEN a.state ELSE 2 END';
        } else {
            $subquery       .= ' AND parent.published != 1 GROUP BY cat.id';
            $publishedWhere = 'CASE WHEN badcats.id is null THEN a.state ELSE 0 END';
        }

        $query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

        if ($this->getState('filter.access')) {
            $groups = implode(',', $user ? $user->getAuthorisedViewLevels() : [1]);
            $query->where('a.access IN (' . $groups . ')')
                ->where('c.access IN (' . $groups . ')');
        }

        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($publishedWhere . ' = ' . (int) $published);
        } elseif (is_array($published)) {
            ArrayHelper::toInteger($published);
            $query->where($publishedWhere . ' IN (' . implode(',', $published) . ')');
        }

        $query->where('a.published = 1');

        $nullDate = $db->quote($db->getNullDate());
        $nowDate  = $db->quote(Factory::getDate()->toSql());

        if ($user && !$user->authorise('core.edit.state', 'com_churchdirectory') && !$user->authorise('core.edit', 'com_churchdirectory')) {
            $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
                ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
        }

        $dateFiltering = $this->getState('filter.date_filtering', 'off');
        $dateField     = (string) $this->getState('filter.date_field', 'a.created');

        if ($dateFiltering === 'range') {
            $start = $db->quote($this->getState('filter.start_date_range', $nullDate));
            $end   = $db->quote($this->getState('filter.end_date_range', $nullDate));
            $query->where('(' . $dateField . ' >= ' . $start . ' AND ' . $dateField . ' <= ' . $end . ')');
        } elseif ($dateFiltering === 'relative') {
            $relative = (int) $this->getState('filter.relative_date', 0);
            $query->where($dateField . ' >= DATE_SUB(' . $nowDate . ', INTERVAL ' . $relative . ' DAY)');
        }

        $query->where('a.mstatus = ' . (int) $this->getState('filter.mstatus'));

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

        if ($search = $this->getState('filter.search')) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } elseif (stripos($search, 'suburb:') === 0) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 7)), true)) . '%');
                $query->where($db->quoteName('a.suburb') . ' LIKE ' . $search);
            } elseif (stripos($search, 'address:') === 0) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 8)), true)) . '%');
                $query->where($db->quoteName('a.address') . ' LIKE ' . $search);
            } elseif (stripos($search, 'zip:') === 0) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 4)), true)) . '%');
                $query->where('a.postcode LIKE ' . $search);
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(' . $db->quoteName('a.name') . ' LIKE ' . $search . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $search . ')');
            }
        }

        return $query;
    }

    /**
     * Pull list state from request + active menu params.
     *
     * @since   2.0.0
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_churchdirectory');
        $format = $app->getInput()->getWord('format', '');

        $this->setState('list.limit', $format === 'feed' ? $app->get('feed_limit') : 0);
        $this->setState('list.start', $app->getInput()->getInt('limitstart', 0));

        $menuParams = new Registry();

        if ($menu = $app->getMenu()?->getActive()) {
            $menuParams->loadString($menu->params);
        }

        $merged   = clone $params;
        $merged->merge($menuParams);
        $orderCol = $app->getInput()->get('filter_order', $merged->get('dinitial_sort', 'ordering'));

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

        if ($user && !$user->authorise('core.edit.state', 'com_churchdirectory') && !$user->authorise('core.edit', 'com_churchdirectory')) {
            $this->setState('filter.published', 1);
            $this->setState('filter.publish_date', true);
        }

        $this->setState('filter.mstatus', $app->getInput()->get('filter_mstatus', $merged->get('mstatus', '0')));
        $this->setState('filter.language', $app->getLanguageFilter());
        $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
        $this->setState('params', $params);
    }

    /**
     * Load the active category and its navigation neighbors.
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
            'countItems' => $menuParams->get('db_show_cat_items', 1) || $menuParams->get('db_show_empty_categories', 0),
        ];

        $categories = Categories::getInstance('Churchdirectory', $options);
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
     * Persist the search term to user state and run the query.
     *
     * @return  array<int, object>|false
     *
     * @since   2.0.0
     */
    public function getSearch()
    {
        $q = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');

        return $q ? $this->getItems() : false;
    }
}
