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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * Lists members that still need a geocode (lat=0, lng=0) joined to any
 * existing failure rows on `#__cwmconnect_geoupdate`.
 *
 * @since  2.0.0
 */
class GeostatusModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @throws \Exception
     * @since   2.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'lname', 'a.lname',
                'alias', 'a.alias',
                'address', 'a.address',
                'suburb', 'a.suburb',
                'postcode', 'a.postcode',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'catid', 'a.catid', 'category_title',
                'user_id', 'a.user_id',
                'state', 'a.state',
                'access', 'a.access', 'access_level',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'ordering', 'a.ordering',
                'featured', 'a.featured',
                'language', 'a.language',
                'publish_up', 'a.publish_up',
                'publish_down', 'a.publish_down',
                'ul.name', 'linked_user',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   Ordering
     * @param   string  $direction  Direction
     *
     * @return  void
     *
     * @throws \Exception
     * @since   2.0.0
     */
    protected function populateState($ordering = 'a.name', $direction = 'asc'): void
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        if ($layout = $input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        $this->setState(
            'filter.search',
            $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string')
        );
        $this->setState(
            'filter.access',
            $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int')
        );
        $this->setState(
            'filter.published',
            $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string')
        );
        $this->setState(
            'filter.category_id',
            $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', null)
        );
        $this->setState(
            'filter.language',
            $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string')
        );

        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   2.0.0
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.access');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  QueryInterface
     *
     * @since   2.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $user  = Factory::getApplication()->getIdentity();
        $query = $db->getQuery(true);

        $query->select(
            $this->getState(
                'list.select',
                'a.id, a.name, a.lname, a.alias, a.address, a.state, a.suburb, a.postcode, a.checked_out, '
                . 'a.checked_out_time, a.catid, a.user_id, a.published, a.access, a.created, a.created_by, '
                . 'a.ordering, a.featured, a.language, a.publish_up, a.publish_down'
            )
        );
        $query->from($db->quoteName('#__cwmconnect_details', 'a'));
        $query->select($db->quoteName('u') . '.*');
        $query->join(
            'LEFT',
            $db->quoteName('#__cwmconnect_geoupdate', 'u')
            . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('u.member_id')
        );

        $query->where($db->quoteName('a.lat') . ' = 0');
        $query->where($db->quoteName('a.lng') . ' = 0');

        $query->select($db->quoteName('ul.name', 'linked_user'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'ul')
                . ' ON ' . $db->quoteName('ul.id') . ' = ' . $db->quoteName('a.user_id')
            );

        $query->select($db->quoteName('l.title', 'language_title'))
            ->join(
                'LEFT',
                $db->quoteName('#__languages', 'l')
                . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language')
            );

        $query->select($db->quoteName('uc.name', 'editor'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'uc')
                . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
            );

        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join(
                'LEFT',
                $db->quoteName('#__viewlevels', 'ag')
                . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access')
            );

        $query->select($db->quoteName('c.title', 'category_title'))
            ->join(
                'LEFT',
                $db->quoteName('#__categories', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid')
            );

        if ($access = $this->getState('filter.access')) {
            $query->where($db->quoteName('a.access') . ' = ' . (int) $access);
        }

        if ($user && !$user->authorise('core.admin')) {
            $groups = implode(',', array_map('intval', $user->getAuthorisedViewLevels()));

            if ($groups !== '') {
                $query->where($db->quoteName('a.access') . ' IN (' . $groups . ')');
            }
        }

        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
        } elseif ($published === '') {
            $query->where(
                '(' . $db->quoteName('a.published') . ' = 0 OR ' . $db->quoteName('a.published') . ' = 1)'
            );
        }

        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId)) {
            $query->where($db->quoteName('a.catid') . ' = ' . (int) $categoryId);
        }

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos((string) $search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr((string) $search, 3));
            } else {
                $like = $db->quote('%' . $db->escape((string) $search, true) . '%');
                $query->where(
                    '(' . $db->quoteName('a.name') . ' LIKE ' . $like
                    . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $like . ')'
                );
            }
        }

        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = ' . $db->quote($language));
        }

        $orderCol  = $this->state->get('list.ordering', 'a.name');
        $orderDirn = $this->state->get('list.direction', 'asc');

        if ($orderCol === 'a.ordering' || $orderCol === 'category_title') {
            $orderCol = $db->quoteName('c.title') . ' ' . $orderDirn . ', ' . $db->quoteName('a.ordering');
        }

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
}
