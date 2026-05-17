<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Phase D Table for #__cwmconnect_pc_field_map.
 *
 * Stores one row per PC FieldDefinition ↔ Joomla custom-field pairing.
 * Validation here enforces the integrity that the schema's UNIQUE keys
 * also catch — but with friendly admin-facing error messages.
 *
 * @since  __DEPLOY_VERSION__
 */
class PcFieldMapTable extends Table
{
    public ?int $id = 0;

    public ?int $pc_field_id = 0;

    public ?string $pc_field_slug = '';

    public ?string $pc_field_name = '';

    public ?int $joomla_field_id = 0;

    public ?string $created_at = null;

    public ?string $updated_at = null;

    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__cwmconnect_pc_field_map', 'id', $db);
    }

    /**
     * Validate before save. The DB's UNIQUE indexes are the source of truth;
     * this method just turns collisions into actionable admin messages.
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function check(): bool
    {
        if ((int) $this->pc_field_id <= 0) {
            $this->setError(Text::_('COM_CWMCONNECT_PC_FIELD_MAP_ERR_NO_PC_FIELD'));

            return false;
        }

        if ((int) $this->joomla_field_id <= 0) {
            $this->setError(Text::_('COM_CWMCONNECT_PC_FIELD_MAP_ERR_NO_JOOMLA_FIELD'));

            return false;
        }

        $db    = $this->getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__cwmconnect_pc_field_map'))
            ->where($db->quoteName('pc_field_id') . ' = ' . (int) $this->pc_field_id);
        $db->setQuery($query);
        $existing = (int) $db->loadResult();

        if ($existing > 0 && $existing !== (int) $this->id) {
            $this->setError(Text::_('COM_CWMCONNECT_PC_FIELD_MAP_ERR_PC_FIELD_TAKEN'));

            return false;
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__cwmconnect_pc_field_map'))
            ->where($db->quoteName('joomla_field_id') . ' = ' . (int) $this->joomla_field_id);
        $db->setQuery($query);
        $existing = (int) $db->loadResult();

        if ($existing > 0 && $existing !== (int) $this->id) {
            $this->setError(Text::_('COM_CWMCONNECT_PC_FIELD_MAP_ERR_JOOMLA_FIELD_TAKEN'));

            return false;
        }

        return true;
    }

    /**
     * Stamp timestamps before delegating to the parent store.
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function store($updateNulls = false): bool
    {
        $now = Factory::getDate()->toSql();

        if ((int) $this->id > 0) {
            $this->updated_at = $now;
        } elseif (!$this->created_at) {
            $this->created_at = $now;
        }

        return parent::store($updateNulls);
    }
}
