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

use CWM\Component\Cwmconnect\Administrator\Helper\PcLockedFields;
use CWM\Component\Cwmconnect\Administrator\Table\MemberTable;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\Database\ParameterType;

/**
 * Phase H: self-service portal model. Resolves the current Joomla user's
 * paired member row, renders the edit form with PC-sourced fields locked
 * (spec §8), and rejects writes that would mutate locked columns (spec §8.3).
 *
 * @since  2.0.0
 */
class MyprofileModel extends FormModel
{
    /** @var string Model context for state caching and form events. */
    protected $context = 'com_cwmconnect.myprofile';

    /** @var object|false|null Cached member row for the current user. */
    private object|false|null $item = null;

    /**
     * Load the portal form. PC-sourced columns are marked readonly so the
     * browser respects the lock; {@see detectLockedFieldChanges()} provides
     * the server-side enforcement for URL-hacked payloads.
     *
     * @since  2.0.0
     */
    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm($this->context, 'myprofile', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        $item   = $this->getItemForCurrentUser();
        $locked = PcLockedFields::forItem($item ?: null);

        foreach ($locked as $field) {
            $form->setFieldAttribute($field, 'readonly', 'true');
        }

        return $form;
    }

    /**
     * Hand back the persistent row as form data when one exists. Falls back to
     * the user-state buffer so a failed save round-trips the user's edits.
     *
     * @return  array<string, mixed>
     *
     * @since   2.0.0
     */
    protected function loadFormData(): array
    {
        $buffered = (array) Factory::getApplication()->getUserState('com_cwmconnect.myprofile.data', []);

        if ($buffered !== []) {
            return $buffered;
        }

        $item = $this->getItemForCurrentUser();

        if ($item === false || $item === null) {
            return [];
        }

        return get_object_vars($item);
    }

    /**
     * Load the member row paired to the current logged-in user. Returns false
     * when the viewer has no paired row (handled by the view — spec §8.1).
     *
     * @since  2.0.0
     */
    public function getItemForCurrentUser(): object|false
    {
        if ($this->item !== null) {
            return $this->item;
        }

        $userId = (int) (Factory::getApplication()->getIdentity()?->id ?? 0);

        if ($userId <= 0) {
            return $this->item = false;
        }

        return $this->item = $this->loadItemByUserId($userId);
    }

    /**
     * Direct DB lookup keyed on `user_id`. Schema invariant since Phase H.1:
     * `user_id` is nullable + UNIQUE, so at most one row can match.
     *
     * @since  2.0.0
     */
    protected function loadItemByUserId(int $userId): object|false
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->where($db->quoteName('published') . ' > -2')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $row = $db->setQuery($query)->loadObject();

        return $row ?: false;
    }

    /**
     * Persist a portal save. Short-circuits on locked-field tampering with the
     * spec §8.3 flash so URL-hacked POSTs can't sneak past the readonly UI.
     *
     * @param   array<string, mixed>  $data
     *
     * @since   2.0.0
     */
    public function save(array $data): bool
    {
        $item = $this->getItemForCurrentUser();

        if ($item === false) {
            $this->setError(Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_NOT_PAIRED'));

            return false;
        }

        $violations = self::detectLockedFieldChanges($item, $data);

        if ($violations !== []) {
            $this->setError(Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_LOCKED_FIELD'));

            return false;
        }

        $table = new MemberTable($this->getDatabase());

        if (!$table->load((int) $item->id)) {
            $this->setError(Text::_('COM_CWMCONNECT_MYPROFILE_ERROR_LOAD_FAILED'));

            return false;
        }

        $allowed = self::editableColumns($item);
        $bind    = array_intersect_key($data, array_flip($allowed));

        if (!$table->bind($bind) || !$table->check() || !$table->store()) {
            $this->setError((string) $table->getError());

            return false;
        }

        return true;
    }

    /**
     * Pure helper: which locked columns does the incoming payload try to
     * mutate? Returns the list of column names whose new value differs from
     * the persistent value. Empty list ⇒ save is safe to proceed.
     *
     * @param   object                $item  Persistent row.
     * @param   array<string, mixed>  $data  Incoming form data.
     *
     * @return  list<string>
     *
     * @since   2.0.0
     */
    public static function detectLockedFieldChanges(object $item, array $data): array
    {
        $locked     = PcLockedFields::forItem($item);
        $violations = [];

        foreach ($locked as $column) {
            if (!\array_key_exists($column, $data)) {
                continue;
            }

            $current = $item->{$column} ?? null;

            if ((string) $data[$column] !== (string) $current) {
                $violations[] = $column;
            }
        }

        return $violations;
    }

    /**
     * The portal-writable column set for a given persistent row. PC-linked
     * rows get an inverse-of-locked allowlist; local-only rows get the full
     * portal column list.
     *
     * @return  list<string>
     *
     * @since   2.0.0
     */
    public static function editableColumns(object $item): array
    {
        $all = [
            'name', 'surname', 'lname',
            'email_to', 'telephone', 'mobile',
            'address', 'suburb', 'state', 'postcode', 'country',
            'birthdate', 'anniversary',
            'display_in_directory',
            'sortname1', 'sortname2', 'sortname3',
        ];

        $locked = PcLockedFields::forItem($item);

        return array_values(array_diff($all, $locked));
    }
}
