<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase D: read access for PC field → Joomla custom-field mappings.
 *
 * The sync engine resolves each incoming PC FieldDatum against this
 * lookup before handing the (joomla_field_id, value) pair off to
 * Joomla's FieldsHelper. Writes (create/update/delete) happen through
 * the admin model directly — the engine never mutates the map.
 *
 * Returned rows are plain associative arrays so a test double can be
 * built without spinning up a DB:
 *   ['id' => int, 'pc_field_id' => int, 'pc_field_slug' => string,
 *    'pc_field_name' => string, 'joomla_field_id' => int].
 *
 * @since  __DEPLOY_VERSION__
 */
interface FieldMapRepositoryInterface
{
    /**
     * Return a lookup keyed by `pc_field_id` covering every saved mapping.
     * Empty array when none exist.
     *
     * @return  array<int, array{id: int, pc_field_id: int, pc_field_slug: string, pc_field_name: string, joomla_field_id: int}>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function allKeyedByPcFieldId(): array;

    /**
     * Phase F: list the Joomla custom-field NAMES (column from
     * `#__fields.name`, not `title`) that should render read-only on
     * any `com_cwmconnect.member` form because their value is owned
     * by Planning Center.
     *
     * Returned as names (not ids) because `Form::setFieldAttribute()`
     * keys off the field's name. Custom-field rows whose
     * `joomla_field_id` no longer resolves to an existing `#__fields`
     * row are silently skipped — the mapping list view already
     * surfaces the broken pairing.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function lockedJoomlaFieldNames(): array;
}
