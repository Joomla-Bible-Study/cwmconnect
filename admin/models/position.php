<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Item Model for a Position.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryModelPosition extends JModelAdmin
{
	const NAME = null;

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

		return true;
	}

	/**
	 * Returns a Table object, always creating it
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since    1.7.0
	 */
	public function getTable($type = 'Position', $prefix = 'ChurchDirectoryTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the row form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 *
	 * @since    1.7.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		jimport('joomla.form.form');
		JForm::addFieldPath('JPATH_ADMINISTRATOR/components/com_users/models/fields');

		// Get the form.
		$form = $this->loadForm('com_churchdirectory.position', 'position', ['control' => 'jform', 'load_data' => $loadData]);

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
		$data = JFactory::getApplication()->getUserState('com_churchdirectory.edit.position.data', []);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   ChurchDirectoryTablePosition  $table  A reference to a JTable object.
	 *
	 * @return  void
	 *
	 * @since    1.7.0
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');
		$date = JFactory::getDate();
		$user = JFactory::getUser();

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
				$db = JFactory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__churchdirectory_position');
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Returns the members associated with this position.
	 *
	 * @param   string  $id  The id of the position
	 *
	 * @return array
	 *
	 * @since 1.7.1
	 */
	public function getMembers($id)
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('members.con_position, members.name, members.id');
		$query->from('#__churchdirectory_details AS members');
		$query->order('members.lname DESC');

		$db->setQuery($query->__toString());
		$positions    = $db->loadObjectList();
		$positiontemp = [];
		$positionkey  = null;

		foreach ($positions as $p)
		{
			if ($p->con_position != '')
			{
				$con_position  = explode(',', $p->con_position);
				$positiontemp  = ["name" => $p->name, "con_position" => $con_position, "id" => $p->id];
				$positionkey[] = $positiontemp;
			}
			else
			{
				$positionkey[] = null;
			}
		}

		$position = null;

		if ($positionkey)
		{
			foreach ($positionkey as $d)
			{
				if ($d['con_position'] != '')
				{
					$conpositions = $d['con_position'];
					$key          = array_search($id, $conpositions);

					if ($key === 0)
					{
						$positiontemp1 = ["name" => $d['name'], "id" => $d['id']];
						$position[]    = $positiontemp1;
					}
				}
			}
		}

		$results = $position;

		return $results;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   JTable  $table  A JTable object.
	 *
	 * @return  array  An array of conditions to add to ordering queries.
	 *
	 * @since    1.7.0
	 */
	protected function getReorderConditions($table)
	{
		$condition = [];

		return $condition;
	}
}
