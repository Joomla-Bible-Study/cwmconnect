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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * Phase G: paginated household list. Each row is a `#__cwmconnect_familyunit`
 * joined to a count of its visible members (display_in_directory=1).
 *
 * Households whose ALL members are hidden (children-only, or every adult
 * opted out) still appear in the count but render as empty in the
 * template. We don't filter them at query time because doing so couples
 * the visibility rule to the SQL — keeping it in the template lets the
 * household-aware variants (same-household viewers seeing kids' names)
 * grow without rewriting the model.
 *
 * @since  __DEPLOY_VERSION__
 */
class HouseholdsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'member_count',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.name', $direction = 'asc'): void
    {
        $app = Factory::getApplication();
        $this->setState('filter.search', $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = ''): string
    {
        return parent::getStoreId($id . ':' . $this->getState('filter.search'));
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.description'),
            $db->quoteName('a.image'),
        ])
        ->select('(SELECT COUNT(*) FROM ' . $db->quoteName('#__cwmconnect_details', 'm')
            . ' WHERE ' . $db->quoteName('m.funitid') . ' = ' . $db->quoteName('a.id')
            . ' AND ' . $db->quoteName('m.published') . ' = 1'
            . ' AND ' . $db->quoteName('m.display_in_directory') . ' = 1) AS '
            . $db->quoteName('visible_count'))
        ->from($db->quoteName('#__cwmconnect_familyunit', 'a'))
        ->where($db->quoteName('a.published') . ' = 1');

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where($db->quoteName('a.name') . ' LIKE ' . $like);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.name');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
}
