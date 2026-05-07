<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Churchdirectory\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Item model for a Kml record.
 *
 * @since  2.0.0
 */
class KmlModel extends AdminModel
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

            return $this->getCurrentUser()->authorise('core.delete', 'com_churchdirectory');
        }

        return true;
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
    public function getTable($name = 'Kml', $prefix = '', $options = []): Table
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
            'com_churchdirectory.kml',
            'kml',
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
        $data = Factory::getApplication()->getUserState('com_churchdirectory.edit.kml.data', []);

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
            if (empty($table->ordering)) {
                $db    = $this->getDatabase();
                $query = $db->getQuery(true)
                    ->select('MAX(' . $db->quoteName('ordering') . ')')
                    ->from($db->quoteName('#__churchdirectory_kml'));

                $db->setQuery($query);
                $max = (int) $db->loadResult();

                $table->ordering = $max + 1;
            }
        }
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
