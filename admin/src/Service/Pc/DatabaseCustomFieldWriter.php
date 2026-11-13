<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Production {@see CustomFieldWriterInterface}: writes `#__fields_values`
 * directly, the way com_fields stores them.
 *
 * Not through com_fields' `FieldModel::setFieldValue()`, which checks
 * `core.edit.value` against the session user. A scheduled sync runs without
 * one (from the CLI, or the lazy scheduler in a visitor's request), so it
 * refused every value. The sync writes a member's mapped fields as it writes
 * their other columns: the mapping an administrator set up is the authority.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DatabaseCustomFieldWriter implements CustomFieldWriterInterface
{
    /**
     * The member custom fields, by id: whether each holds several values, a
     * row per value, or one. Loaded on first use.
     *
     * @var    array<int, bool>|null
     * @since  __DEPLOY_VERSION__
     */
    private ?array $fields = null;

    /**
     * @param   DatabaseInterface  $db  Database driver.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly DatabaseInterface $db) {}

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function writeValues(int $memberId, array $values): int
    {
        // A mapping can outlive the field it points at.
        $values = array_intersect_key($values, $this->fields());

        if ($memberId <= 0 || $values === []) {
            return 0;
        }

        $itemId = (string) $memberId;
        $query  = $this->db->createQuery()
            ->select($this->db->quoteName(['field_id', 'value']))
            ->from($this->db->quoteName('#__fields_values'))
            ->whereIn($this->db->quoteName('field_id'), array_keys($values))
            ->where($this->db->quoteName('item_id') . ' = :itemId')
            ->bind(':itemId', $itemId);

        $stored = [];

        foreach ($this->db->setQuery($query)->loadObjectList() as $row) {
            $stored[(int) $row->field_id][] = (string) $row->value;
        }

        $written = 0;

        foreach ($values as $fieldId => $list) {
            $rows = $this->rows($fieldId, $list);

            if (self::sameRows($stored[$fieldId] ?? [], $rows)) {
                continue;
            }

            $this->replace($fieldId, $itemId, $rows);
            $written++;
        }

        // As com_fields does after a write, so a later read in this request
        // doesn't get the old values.
        if ($written > 0) {
            FieldsHelper::clearFieldsCache();
        }

        return $written;
    }

    /**
     * The rows a field keeps its values in. A field that holds several gets a
     * row per value, as com_fields stores them; any other gets them joined in
     * one, since its form input can't take a list.
     *
     * @param   int           $fieldId  #__fields.id.
     * @param   list<string>  $values   The values to hold.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function rows(int $fieldId, array $values): array
    {
        $values = array_values(array_map('strval', $values));

        if ($values === [] || $this->fields()[$fieldId]) {
            return $values;
        }

        // Sorted, so the same options in another order are the same value.
        sort($values);

        return [implode(', ', $values)];
    }

    /**
     * Whether two sets of rows hold the same values, in any order: Planning
     * Center reordering a field's options isn't a change.
     *
     * @param   list<string>  $a  Rows.
     * @param   list<string>  $b  Rows.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function sameRows(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }

    /**
     * Replace whatever a field holds for a member with these rows.
     *
     * @param   int           $fieldId  #__fields.id.
     * @param   string        $itemId   Member row id, as com_fields keys it.
     * @param   list<string>  $rows     Values, one per row. None leaves it empty.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function replace(int $fieldId, string $itemId, array $rows): void
    {
        $query = $this->db->createQuery()
            ->delete($this->db->quoteName('#__fields_values'))
            ->where($this->db->quoteName('field_id') . ' = :fieldId')
            ->where($this->db->quoteName('item_id') . ' = :itemId')
            ->bind(':fieldId', $fieldId, ParameterType::INTEGER)
            ->bind(':itemId', $itemId);

        $this->db->setQuery($query)->execute();

        foreach ($rows as $value) {
            $row = (object) ['field_id' => $fieldId, 'item_id' => $itemId, 'value' => $value];
            $this->db->insertObject('#__fields_values', $row);
        }
    }

    /**
     * The member custom fields, and whether each holds several values:
     * checkboxes always do, the types with a `multiple` option when it's set.
     *
     * @return  array<int, bool>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function fields(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $context = 'com_cwmconnect.member';
        $query   = $this->db->createQuery()
            ->select($this->db->quoteName(['id', 'type', 'fieldparams']))
            ->from($this->db->quoteName('#__fields'))
            ->where($this->db->quoteName('context') . ' = :context')
            ->bind(':context', $context);

        $this->fields = [];

        foreach ($this->db->setQuery($query)->loadObjectList() as $field) {
            $this->fields[(int) $field->id] = $field->type === 'checkboxes'
                || new Registry($field->fieldparams)->get('multiple', false);
        }

        return $this->fields;
    }
}
