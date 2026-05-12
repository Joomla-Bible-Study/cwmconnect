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
use Joomla\CMS\Language\Associations;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\QueryInterface;
use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of Member records.
 *
 * @since  2.0.0
 */
class MembersModel extends ListModel
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
                'funitid', 'funitname',
                'alias', 'a.alias',
                'checked_out', 'a.checked_out',
                'checked_out_time', 'a.checked_out_time',
                'catid', 'a.catid', 'category_title',
                'user_id', 'a.user_id',
                'state', 'a.state',
                'postcode', 'a.postcode',
                'access', 'a.access', 'access_level',
                'created', 'a.created',
                'created_by', 'a.created_by',
                'ordering', 'a.ordering',
                'featured', 'a.featured',
                'language', 'a.language',
                'publish_up', 'a.publish_up',
                'publish_down', 'a.publish_down',
                'ul.name', 'linked_user',
                'mstatus', 'a.mstatus',
                'tag',
                'level', 'c.level',
            ];

            if (Associations::isEnabled()) {
                $config['filter_fields'][] = 'association';
            }
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

        $forcedLanguage = $input->get('forcedLanguage', '', 'cmd');

        if ($layout = $input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        if ($forcedLanguage) {
            $this->context .= '.' . $forcedLanguage;
        }

        $this->setState(
            'filter.search',
            $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string')
        );
        $this->setState(
            'filter.published',
            $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string')
        );
        $this->setState(
            'filter.category_id',
            $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', '', 'string')
        );
        $this->setState(
            'filter.access',
            $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', '', 'cmd')
        );
        $this->setState(
            'filter.language',
            $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string')
        );
        $this->setState(
            'filter.tag',
            $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '', 'string')
        );
        $this->setState(
            'filter.level',
            $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', null, 'int')
        );
        $this->setState(
            'filter.mstatus',
            $this->getUserStateFromRequest($this->context . '.filter.mstatus', 'filter_mstatus', '', 'string')
        );

        parent::populateState($ordering, $direction);

        if (!empty($forcedLanguage)) {
            $this->setState('filter.language', $forcedLanguage);
        }
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
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . serialize($this->getState('filter.access'));
        $id .= ':' . $this->getState('filter.language');
        $id .= ':' . $this->getState('filter.mstatus');
        $id .= ':' . $this->getState('filter.tag');
        $id .= ':' . $this->getState('filter.level');

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
                        'a.id, a.name, a.lname, a.funitid, a.alias, a.checked_out, a.checked_out_time, a.catid, a.user_id'
                        . ', a.published, a.access, a.created, a.created_by, a.ordering, a.featured, a.language, a.mstatus'
                        . ', a.image, a.publish_up, a.publish_down'
                    )
                )
            )
        );
        $query->from($db->quoteName('#__churchdirectory_details', 'a'));

        // Linked user.
        $query->select([
            $db->quoteName('ul.name', 'linked_user'),
            $db->quoteName('ul.email'),
        ])
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'ul')
                . ' ON ' . $db->quoteName('ul.id') . ' = ' . $db->quoteName('a.user_id')
            );

        // Family unit.
        $query->select($db->quoteName('fu.name', 'funitname'))
            ->join(
                'LEFT',
                $db->quoteName('#__churchdirectory_familyunit', 'fu')
                . ' ON ' . $db->quoteName('fu.id') . ' = ' . $db->quoteName('a.funitid')
            );

        // Language.
        $query->select($db->quoteName('l.title', 'language_title'))
            ->join(
                'LEFT',
                $db->quoteName('#__languages', 'l')
                . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language')
            );

        // Checked-out user.
        $query->select($db->quoteName('uc.name', 'editor'))
            ->join(
                'LEFT',
                $db->quoteName('#__users', 'uc')
                . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out')
            );

        // Asset groups.
        $query->select($db->quoteName('ag.title', 'access_level'))
            ->join(
                'LEFT',
                $db->quoteName('#__viewlevels', 'ag')
                . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access')
            );

        // Categories.
        $query->select($db->quoteName('c.title', 'category_title'))
            ->join(
                'LEFT',
                $db->quoteName('#__categories', 'c')
                . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid')
            );

        // Associations.
        if (Associations::isEnabled()) {
            $query->select(
                'COUNT(' . $db->quoteName('asso2.id') . ') > 1 AS ' . $db->quoteName('association')
            )
                ->join(
                    'LEFT',
                    $db->quoteName('#__associations', 'asso')
                    . ' ON ' . $db->quoteName('asso.id') . ' = ' . $db->quoteName('a.id')
                    . ' AND ' . $db->quoteName('asso.context') . ' = ' . $db->quote('com_churchdirectory.item')
                )
                ->join(
                    'LEFT',
                    $db->quoteName('#__associations', 'asso2')
                    . ' ON ' . $db->quoteName('asso2.key') . ' = ' . $db->quoteName('asso.key')
                )
                ->group($db->quoteName([
                    'a.id',
                    'a.name',
                    'a.lname',
                    'a.funitid',
                    'a.alias',
                    'a.checked_out',
                    'a.checked_out_time',
                    'a.catid',
                    'a.user_id',
                    'a.published',
                    'a.access',
                    'a.created',
                    'a.created_by',
                    'a.ordering',
                    'a.featured',
                    'a.language',
                    'a.mstatus',
                    'a.image',
                    'a.publish_up',
                    'a.publish_down',
                    'ul.name',
                    'ul.email',
                    'fu.name',
                    'l.title',
                    'uc.name',
                    'ag.title',
                    'c.title',
                    'c.level',
                ]));
        }

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

        // Filter by single or multiple categories.
        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId)) {
            $query->where($db->quoteName('a.catid') . ' = ' . (int) $categoryId);
        } elseif (\is_array($categoryId)) {
            $query->whereIn($db->quoteName('a.catid'), ArrayHelper::toInteger($categoryId));
        }

        // Filter by search.
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } elseif (stripos($search, 'author:') === 0) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 7)), true)) . '%');
                $query->where(
                    '(' . $db->quoteName('uc.name') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('uc.username') . ' LIKE ' . $search . ')'
                );
            } elseif (stripos($search, 'zip:') === 0) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 4)), true)) . '%');
                $query->where($db->quoteName('a.postcode') . ' LIKE ' . $search);
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where(
                    '(' . $db->quoteName('a.name') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $search . ')'
                );
            }
        }

        // Filter on language.
        if ($language = $this->getState('filter.language')) {
            $query->where($db->quoteName('a.language') . ' = ' . $db->quote($language));
        }

        // Filter on member status.
        if ($mstatus = $this->getState('filter.mstatus')) {
            $query->where($db->quoteName('a.mstatus') . ' = ' . $db->quote($mstatus));
        }

        // Filter by tag.
        $tagId = $this->getState('filter.tag');

        if (is_numeric($tagId)) {
            $query->where($db->quoteName('tagmap.tag_id') . ' = ' . (int) $tagId)
                ->join(
                    'LEFT',
                    $db->quoteName('#__contentitem_tag_map', 'tagmap')
                    . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
                    . ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_churchdirectory.member')
                );
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
