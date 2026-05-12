<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Item model for a Familyunit.
 *
 * @since  2.0.0
 */
class FamilyunitModel extends AdminModel
{
    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    protected function canDelete($record): bool
    {
        if (!empty($record->id)) {
            if ((int) $record->published !== -2) {
                return false;
            }

            return $this->getCurrentUser()->authorise('core.delete', 'com_cwmconnect');
        }

        return false;
    }

    /**
     * Returns a Table object, always creating it.
     *
     * @param   string  $name     The table name.
     * @param   string  $prefix   The class prefix.
     * @param   array   $options  Configuration array for the model.
     *
     * @return  Table
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getTable($name = 'Familyunit', $prefix = '', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get the row form.
     *
     * @param   array  $data      Data for the form.
     * @param   bool   $loadData  True if the form is to load its own data.
     *
     * @return  mixed  A Form object on success, false on failure
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getForm($data = [], $loadData = true): mixed
    {
        $form = $this->loadForm(
            'com_cwmconnect.familyunit',
            'familyunit',
            ['control' => 'jform', 'load_data' => $loadData]
        );

        if (empty($form)) {
            return false;
        }

        if (!$this->canEditState((object) $data)) {
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('published', 'disabled', 'true');

            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    protected function loadFormData(): mixed
    {
        $data = Factory::getApplication()->getUserState('com_cwmconnect.edit.familyunit.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param   Table  $table  A reference to a Table object.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function prepareTable($table): void
    {
        $table->name  = htmlspecialchars_decode((string) $table->name, ENT_QUOTES);
        $table->alias = ApplicationHelper::stringURLSafe((string) $table->alias);

        if (empty($table->alias)) {
            $table->alias = ApplicationHelper::stringURLSafe((string) $table->name);
        }

        if (empty($table->id)) {
            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select('MAX(' . $db->quoteName('ordering') . ')')
                    ->from($db->quoteName('#__cwmconnect_familyunit'));

                $db->setQuery($query);
                $max = (int) $db->loadResult();

                $table->ordering = $max + 1;
            }
        }
    }

    /**
     * Returns a list of members associated with this family unit.
     *
     * @return  array|null
     *
     * @since   2.0.0
     */
    public function getMembers(): ?array
    {
        $item = $this->getItem();

        if (empty($item->id)) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['members.id', 'members.name']))
            ->from($db->quoteName('#__cwmconnect_details', 'members'))
            ->where($db->quoteName('members.funitid') . ' = ' . (int) $item->id)
            ->order($db->quoteName('members.lname') . ' DESC');

        $db->setQuery($query);

        return $db->loadObjectList();
    }

    /**
     * A protected method to get a set of ordering conditions.
     *
     * @param   Table  $table  A Table object.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    protected function getReorderConditions($table): array
    {
        return [];
    }
}
