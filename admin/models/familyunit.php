<?php

/**
 * FamilyUnit model
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Item Model for a FamilyUnit.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryModelFamilyUnit extends JModelAdmin
{
	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return    boolean    True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since    1.7.0
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id))
		{
			if ($record->published != -2)
			{
				return false;
			}

			$user = JFactory::getUser();

			return $user->authorise('core.delete');
		}

		return false;
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return    boolean  True if allowed to change the state of the record. Defaults to the permission set in the
	 *                     component.
	 *
	 * @since    1.7.0
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		return parent::canEditState($record);
	}

	/**
	 * Returns a Table object, always creating it
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    JTable    A database object
	 *
	 * @since    1.7.0
	 */
	public function getTable($type = 'FamilyUnit', $prefix = 'ChurchDirectoryTable', $config = [])
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the row form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    mixed    A JForm object on success, false on failure
	 *
	 * @since    1.7.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		jimport('joomla.form.form');
		JForm::addFieldPath('JPATH_ADMINISTRATOR/components/com_users/models/fields');

		// Get the form.
		$form = $this->loadForm('com_churchdirectory.familyunit', 'familyunit', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    1.7.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_churchdirectory.edit.familyunit.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   ChurchDirectoryTableFamilyUnit  $table  ?
	 *
	 * @return    void
	 *
	 * @since    1.7.0
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		$table->name  = htmlspecialchars_decode($table->name, ENT_QUOTES);
		$table->alias = JApplicationHelper::stringURLSafe($table->alias);

		if (empty($table->alias))
		{
			$table->alias = JApplicationHelper::stringURLSafe($table->name);
		}

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (empty($table->ordering))
			{
				$query = $this->_db->getQuery(true);
				$query->select('MAX(ordering)')->from($this->_db->q('#__churchdirectory_familyunit'));
				$this->_db->setQuery($query);
				$max = $this->_db->loadResult();

				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Returns a list of mediafiles associated with this study
	 *
	 * @return Object|bool;
	 *
	 * @since   7.0
	 */
	public function getMembers()
	{
		if ($this->getItem()->id !== null)
		{
			$query = $this->_db->getQuery(true);

			$query->select('members.id, members.name');
			$query->from('#__churchdirectory_details AS members');
			$query->where('members.funitid = ' . (int) $this->getItem()->id);
			$query->order('members.lname DESC');

			$this->_db->setQuery($query->__toString());

			return $this->_db->loadObjectList();
		}

		return false;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   JTable  $table  A record object.
	 *
	 * @return   array    An array of conditions to add to add to ordering queries.
	 *
	 * @since    1.7.0
	 */
	protected function getReorderConditions($table)
	{
		$condition = [];

		return $condition;
	}
}
