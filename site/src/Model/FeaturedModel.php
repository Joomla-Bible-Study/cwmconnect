<?php

/**
 * @package    Churchdirectory.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * Featured-member list model. Lists every published+featured member the user
 * can read, paginated and filterable by category / language.
 *
 * @since  2.0.0
 */
class FeaturedModel extends ListModel
{
    /**
     * @param   array<string, mixed>  $config  Configuration; provides filter_fields when absent.
     *
     * @since   2.0.0
     */
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
            ];
        }

        parent::__construct($config);
    }

    /**
     * Decode each item's params into a Registry alongside the parent fetch.
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
            $params = new Registry();
            $params->loadString((string) $item->params);
            $item->params = $params;
        }

        return $items;
    }

    /**
     * SQL: featured members visible to the current user, respecting category
     * publish state up the tree and optional category/language filters.
     *
     * @return  QueryInterface
     *
     * @since   2.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $user   = Factory::getApplication()->getIdentity();
        $groups = implode(',', $user ? $user->getAuthorisedViewLevels() : [1]);

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($this->getState('list.select', 'a.*'))
            ->from($db->quoteName('#__churchdirectory_details', 'a'))
            ->where('a.access IN (' . $groups . ')')
            ->where('a.featured = 1')
            ->join('INNER', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->where('c.access IN (' . $groups . ')');

        if ($categoryId = $this->getState('category.id')) {
            $query->where('a.catid = ' . (int) $categoryId);
        }

        $query->select(
            'c.published as cat_published, '
            . 'CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published'
        );

        $subquery  = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
        $subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
        $subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');
        $subquery .= ' AND parent.published != 1 GROUP BY cat.id';

        $query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

        $state = $this->getState('filter.published');

        if (is_numeric($state)) {
            $nullDate = $db->quote($db->getNullDate());
            $nowDate  = $db->quote(Factory::getDate()->toSql());

            $query->where('a.published = ' . (int) $state)
                ->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
                ->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')')
                ->where('(CASE WHEN badcats.id is null THEN a.published ELSE 0 END) = ' . (int) $state);
        }

        if ($this->getState('filter.language')) {
            $query->where('a.language IN ('
                . $db->quote(Factory::getApplication()->getLanguage()->getTag()) . ', '
                . $db->quote('*') . ')');
        }

        $orderCol = $db->escape((string) $this->getState('list.ordering', 'a.ordering'));
        $orderDir = $db->escape((string) $this->getState('list.direction', 'ASC'));
        $query->order($orderCol . ' ' . $orderDir);

        return $query;
    }

    /**
     * Auto-populate state from request + component params, applying the
     * "non-editors see published only" safety net.
     *
     * @since   2.0.0
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app    = Factory::getApplication();
        $params = ComponentHelper::getParams('com_churchdirectory');

        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
        $this->setState('list.limit', $limit);

        $limitstart = $app->getInput()->getInt('limitstart', 0);
        $this->setState('list.start', $limitstart);

        $orderCol = $app->getInput()->getCmd('filter_order', 'ordering');

        if (!\in_array($orderCol, $this->filter_fields, true)) {
            $orderCol = 'ordering';
        }

        $this->setState('list.ordering', $orderCol);

        $listOrder = strtoupper((string) $app->getInput()->getCmd('filter_order_Dir', 'ASC'));

        if (!\in_array($listOrder, ['ASC', 'DESC', ''], true)) {
            $listOrder = 'ASC';
        }

        $this->setState('list.direction', $listOrder);

        $user = $app->getIdentity();

        if ($user && !$user->authorise('core.edit.state', 'com_churchdirectory') && !$user->authorise('core.edit', 'com_churchdirectory')) {
            $this->setState('filter.published', 1);
            $this->setState('filter.publish_date', true);
        }

        $this->setState('filter.language', $app->getLanguageFilter());
        $this->setState('params', $params);
    }
}
