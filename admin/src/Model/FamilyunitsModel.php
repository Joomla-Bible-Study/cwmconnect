<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;

/**
 * Methods supporting a list of Familyunit records.
 *
 * @since  2.0.0
 */
class FamilyunitsModel extends ListModel
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
                'alias', 'a.alias',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'user_id', 'a.user_id',
                'published', 'a.published',
                'access', 'a.access', 'access_level',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'ordering', 'a.ordering',
                'language', 'a.language',
                'publish_up', 'a.publish_up',
                'publish_down', 'a.publish_down',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   Ordering
     * @param   string  $direction  Direction of the list
     *
     * @return  void
     *
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
        $query = $db->getQuery(true);
        $user  = Factory::getApplication()->getIdentity();

        $query->select(
            $db->quoteName(
                explode(
                    ', ',
                    $this->getState(
                        'list.select',
                        'a.id, a.name, a.alias, a.checked_out, a.checked_out_time, a.user_id'
                        . ', a.published, a.access, a.created, a.created_by, a.ordering, a.language'
                        . ', a.publish_up, a.publish_down'
                    )
                )
            )
        );
        $query->from($db->quoteName('#__churchdirectory_familyunit', 'a'));

        // Join over the users for the linked user.
        $query->select($db->quoteName('ul.name', 'linked_user'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'ul')
                . ' ON ' . $db->quoteName('ul.id') . ' = ' . $db->quoteName('a.user_id')
            );

        // Join over the language.
        $query->select($db->quoteName('l.title', 'language_title'))
            ->join(
                'LEFT',
                $db->quoteName('#__languages', 'l')
                . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language')
            );

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'uc')
                . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
            );

        // Join over the asset groups.
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join(
                'LEFT',
                $db->quoteName('#__viewlevels', 'ag')
                . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access')
            );

        // Filter by access level.
        if ($access = $this->getState('filter.access')) {
            $query->where($db->quoteName('a.access') . ' = ' . (int) $access);
        }

        // Implement view-level access.
        if ($user && !$user->authorise('core.admin')) {
            $groups = $user->getAuthorisedViewLevels();

            if (!empty($groups)) {
                $query->whereIn($db->quoteName('a.access'), $groups);
            }
        }

        // Filter by published state.
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
        } elseif ($published === '') {
            $query->where(
                '(' . $db->quoteName('a.published') . ' = 0 OR ' . $db->quoteName('a.published') . ' = 1)'
            );
        }

        // Filter by search.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } elseif (stripos($search, 'author:') === 0) {
                $search = $db->quote('%' . $db->escape(substr($search, 7), true) . '%');
                $query->where(
                    '(' . $db->quoteName('uc.name') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('uc.username') . ' LIKE ' . $search . ')'
                );
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where(
                    '(' . $db->quoteName('a.name') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $search . ')'
                );
            }
        }

        // Filter on the language.
        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = ' . $db->quote($language));
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.name');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
}
