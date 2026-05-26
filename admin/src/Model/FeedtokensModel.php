<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * List model for admin feed-token management.
 *
 * @since  __DEPLOY_VERSION__
 */
class FeedtokensModel extends ListModel
{
    /**
     * @param   array  $config  Configuration array.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'label', 'a.label',
                'user_id', 'a.user_id',
                'created_at', 'a.created_at',
                'last_used_at', 'a.last_used_at',
                'status',
            ];
        }

        parent::__construct($config);
    }

    /**
     * @param   string  $ordering   Default ordering column.
     * @param   string  $direction  Default ordering direction.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function populateState($ordering = 'a.created_at', $direction = 'desc'): void
    {
        $this->setState(
            'filter.search',
            $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'),
        );
        $this->setState(
            'filter.status',
            $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '', 'cmd'),
        );

        parent::populateState($ordering, $direction);
    }

    /**
     * @param   string  $id  Store cache key seed.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.status');

        return parent::getStoreId($id);
    }

    /**
     * @return  QueryInterface
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery()
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.user_id'),
                $db->quoteName('a.label'),
                $db->quoteName('a.created_at'),
                $db->quoteName('a.last_used_at'),
                $db->quoteName('a.revoked_at'),
            ])
            ->select($db->quoteName('u.name', 'user_name'))
            ->from($db->quoteName('#__cwmconnect_feed_tokens', 'a'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON u.id = a.user_id');

        $search = (string) $this->getState('filter.search');

        if ($search !== '') {
            $like = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where(
                '(' . $db->quoteName('a.label') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('u.name') . ' LIKE ' . $like . ')',
            );
        }

        $status = (string) $this->getState('filter.status');

        if ($status === 'active') {
            $query->where($db->quoteName('a.revoked_at') . ' IS NULL');
        } elseif ($status === 'revoked') {
            $query->where($db->quoteName('a.revoked_at') . ' IS NOT NULL');
        }

        $orderCol  = $this->state->get('list.ordering', 'a.created_at');
        $orderDirn = $this->state->get('list.direction', 'desc');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
}
