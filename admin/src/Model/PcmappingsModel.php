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
 * Phase D: list of `#__cwmconnect_pc_field_map` rows, joined to
 * `#__fields` so the admin sees friendly Joomla field titles instead
 * of opaque numeric ids.
 *
 * @since  __DEPLOY_VERSION__
 */
class PcmappingsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'pc_field_id', 'a.pc_field_id',
                'pc_field_slug', 'a.pc_field_slug',
                'pc_field_name', 'a.pc_field_name',
                'joomla_field_id', 'a.joomla_field_id',
                'created_at', 'a.created_at',
                'updated_at', 'a.updated_at',
                'f.title', 'joomla_field_title',
            ];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.pc_field_name', $direction = 'asc'): void
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        if ($layout = $input->get('layout')) {
            $this->context .= '.' . $layout;
        }

        $this->setState(
            'filter.search',
            $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'),
        );

        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');

        return parent::getStoreId($id);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.pc_field_id'),
            $db->quoteName('a.pc_field_slug'),
            $db->quoteName('a.pc_field_name'),
            $db->quoteName('a.joomla_field_id'),
            $db->quoteName('a.created_at'),
            $db->quoteName('a.updated_at'),
            $db->quoteName('f.title', 'joomla_field_title'),
            $db->quoteName('f.name', 'joomla_field_name'),
        ])
        ->from($db->quoteName('#__cwmconnect_pc_field_map', 'a'))
        ->join(
            'LEFT',
            $db->quoteName('#__fields', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('a.joomla_field_id'),
        );

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $like = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where(
                '(' . $db->quoteName('a.pc_field_name') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('a.pc_field_slug') . ' LIKE ' . $like
                . ' OR ' . $db->quoteName('f.title') . ' LIKE ' . $like . ')',
            );
        }

        $orderCol  = $this->state->get('list.ordering', 'a.pc_field_name');
        $orderDirn = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }
}
