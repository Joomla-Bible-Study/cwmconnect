<?php

/**
 * Member model
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\Registry\Registry;

/**
 * Item Model for a Member.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryModelMember extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $typeAlias = 'com_churchdirectory.member';

	/**
	 * The context used for the associations table
	 *
	 * @var    string
	 * @since  3.4.4
	 */
	protected $associationsContext = 'com_churchdirectory.item';

	/**
	 * Batch copy/move command. If set to false, the batch copy/move command is not supported
	 *
	 * @var  string
	 * @since    1.7.0
	 */
	protected $batch_copymove = 'category_id';

	/**
	 * Allowed batch commands
	 *
	 * @var array
	 * @since    1.7.0
	 */
	protected $batch_commands = [
		'assetgroup_id' => 'batchAccess',
		'language_id'   => 'batchLanguage',
		'tag'           => 'batchTag',
		'user_id'       => 'batchUser'
	];

	protected $tagsObserver;

	protected $type;

	/**
	 * @var ChurchDirectoryTableMember
	 * @since    1.7.0
	 */
	protected $table;

	/**
	 * Method to perform batch operations on an item or a set of items.
	 *
	 * @param   array  $commands  An array of commands to perform.
	 * @param   array  $pks       An array of item ids.
	 * @param   array  $contexts  An array of item contexts.
	 *
	 * @return  boolean  Returns true on success, false on failure.
	 *
	 * @since   2.5
	 */
	public function batch($commands, $pks, $contexts)
	{
		// Sanitize user ids.
		$pks = array_unique($pks);
		Joomla\Utilities\ArrayHelper::toInteger($pks);

		// Remove any values of zero.
		if (array_search(0, $pks, true))
		{
			unset($pks[array_search(0, $pks, true)]);
		}

		if (empty($pks))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JGLOBAL_NO_ITEM_SELECTED'), 'error');

			return false;
		}

		$done = false;

		if (!empty($commands['category_id']))
		{
			$cmd = Joomla\Utilities\ArrayHelper::getValue($commands, 'move_copy', 'c');

			if ($cmd == 'c')
			{
				$result = $this->batchCopy($commands['category_id'], $pks, $contexts);

				if (is_array($result))
				{
					$pks = $result;
				}
				else
				{
					return false;
				}
			}
			elseif ($cmd == 'm' && !$this->batchMove($commands['category_id'], $pks, $contexts))
			{
				return false;
			}

			$done = true;
		}

		if (!empty($commands['assetgroup_id']))
		{
			if (!$this->batchAccess($commands['assetgroup_id'], $pks, $contexts))
			{
				return false;
			}

			$done = true;
		}

		if (!empty($commands['language_id']))
		{
			if (!$this->batchLanguage($commands['language_id'], $pks, $contexts))
			{
				return false;
			}

			$done = true;
		}

		if (strlen($commands['user_id']) > 0)
		{
			if (!$this->batchUser($commands['user_id'], $pks, $contexts))
			{
				return false;
			}

			$done = true;
		}

		if (!$done)
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_INSUFFICIENT_BATCH_INFORMATION'), 'error');

			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Batch copy items to a new category or current.
	 *
	 * @param   integer  $value     The new category.
	 * @param   array    $pks       An array of row IDs.
	 * @param   array    $contexts  An array of item contexts.
	 *
	 * @return  mixed  An array of new IDs on success, boolean false on failure.
	 *
	 * @since    11.1
	 */
	protected function batchCopy($value, $pks, $contexts)
	{
		$categoryId = (int) $value;
		$newIds = [];

		/** @type ChurchDirectoryTableMember $table */
		$table = $this->getTable();
		$i     = 0;

		if (!parent::checkCategoryId($categoryId))
		{
			return false;
		}

		// Check that the category exists
		if ($categoryId)
		{
			$categoryTable = JTable::getInstance('Category');

			if (!$categoryTable->load($categoryId))
			{
				if ($error = $categoryTable->getError())
				{
					// Fatal error
					JFactory::getApplication()->enqueueMessage($error, 'error');

					return false;
				}
				else
				{
					$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'));

					return false;
				}
			}
		}

		if (empty($categoryId))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'));

			return false;
		}

		// Check that the user has create permission for the component
		$user = JFactory::getUser();

		if (!$user->authorise('core.create', 'com_churchdirectory.category.' . $categoryId))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));

			return false;
		}

		// Parent exists so we let's proceed
		while (!empty($pks))
		{
			// Pop the first ID off the stack
			$pk = array_shift($pks);

			$this->table->reset();

			// Check that the row actually exists
			if (!$this->table->load($pk))
			{
				if ($error = $this->table->getError())
				{
					// Fatal error
					$this->setError($error);

					return false;
				}
				else
				{
					// Not fatal error
					$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
					continue;
				}
			}

			// Alter the title & alias
			$data         = $this->generateNewTitle($categoryId, $this->table->alias, $this->table->name);
			$this->table->name  = $data['0'];
			$this->table->alias = $data['1'];

			// Reset the ID because we are making a copy
			$this->table->id = 0;

			// New category ID
			$this->table->catid = $categoryId;

			// Unpublish because we are making a copy
			$this->table->published = 0;

			// Check the row.
			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			$this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);

			// Store the row.
			if (!$this->table->store())
			{
				$this->setError($this->table->getError());

				return false;
			}

			// Get the new item ID
			$newId = $this->table->get('id');

			// Add the new ID to the array
			$newIds[$i] = $newId;
		}

		// Clean the cache
		$this->cleanCache();

		return $newIds;
	}

	/**
	 * Batch change a linked user.
	 *
	 * @param   integer  $value     The new value matching a User ID.
	 * @param   array    $pks       An array of row IDs.
	 * @param   array    $contexts  An array of item contexts.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since   2.5
	 */
	protected function batchUser($value, $pks, $contexts)
	{
		// Set the variables
		$user  = JFactory::getUser();
		$table = $this->getTable();

		foreach ($pks as $pk)
		{
			if ($user->authorise('core.edit', $contexts[$pk]))
			{
				$table->reset();
				$table->load($pk);
				$table->user_id = (int) $value;

				if (!$table->store())
				{
					$this->setError($table->getError());

					return false;
				}
			}
			else
			{
				JFactory::getApplication()->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'), 'error');

				return false;
			}
		}

		// Clean the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return    boolean  True if allowed to delete the record. Defaults to the permission set in the component.
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

			return $user->authorise('core.delete', 'com_churchdirectory.member.' . (int) $record->catid);
		}

		return true;
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return    boolean    True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since    1.7.0
	 */
	protected function canEditState($record)
	{
		$user = JFactory::getUser();

		// Check against the category.
		if (!empty($record->catid))
		{
			return $user->authorise('core.edit.state', 'com_churchdirectory.category.' . (int) $record->catid);
		}
		// Default to component settings if category not known.
		else
		{
			return parent::canEditState($record);
		}
	}

	/**
	 * Save data
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since 1.7.2
	 */
	public function save($data)
	{
		$save = parent::save($data);

		if ($save !== false)
		{
			JLoader::register('ChurchDirectoryModelGeoUpdate', JPATH_ADMINISTRATOR . '/components/com_churchdirectory/models/geoupdate.php');
			$geoupdate = new ChurchDirectoryModelGeoUpdate;
			$geoupdate->run(true, $data['id']);
		}

		return $save;
	}

	/**
	 * Returns a Table object, always creating it
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    ChurchDirectoryTableMember  A database object
	 *
	 * @since    1.7.0
	 */
	public function getTable($type = 'Member', $prefix = 'ChurchDirectoryTable', $config = [])
	{
		/** @var ChurchDirectoryTableMember $table */
		$table = JTable::getInstance($type, $prefix, $config);

		return $table;
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
	public function getForm($data = [], $loadData = true)
	{
		JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_users/models/fields');

		// Get the form.
		$form = $this->loadForm('com_churchdirectory.member', 'member', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('featured', 'disabled', 'true');
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('featured', 'filter', 'unset');
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return    mixed    Object on success, false on failure.
	 *
	 * @since    1.7.0
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the params field to an array.
			$registry = new Registry;
			$registry->loadString($item->attribs);
			$item->attribs = $registry->toArray();

			// Convert the params field to an array.
			$registry = new Registry;
			$registry->loadString($item->metadata);
			$item->metadata = $registry->toArray();
		}

		// Load associated contact items
		$assoc = JLanguageAssociations::isEnabled();

		if ($assoc)
		{
			$item->associations = array();

			if ($item->id != null)
			{
				$associations = JLanguageAssociations::getAssociations('com_churchdirectory',
					'#__churchdirectory_details', 'com_churchdirectory.item', $item->id
				);

				foreach ($associations as $tag => $association)
				{
					$item->associations[$tag] = $association->id;
				}
			}
		}

		// Load item tags
		if (!empty($item->id))
		{
			$item->tags = new JHelperTags;
			$item->tags->getTagIds($item->id, 'com_churchdirectory.member');
		}

		return $item;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed    The data for the form.
	 *
	 * @since    1.7.0
	 */
	protected function loadFormData()
	{
		$app = JFactory::getApplication();

		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_churchdirectory.edit.member.data', array());

		if (empty($data))
		{
			$data               = $this->getItem();
			$data->con_position = explode(',', $data->con_position);

			// Prime some default values.
			if ($this->getState('member.id') == 0)
			{
				$data->set('catid',
					JFactory::getApplication()->input->getInt('catid', $app->getUserState('com_churchdirectory.members.filter.category_id'), 'int')
				);
			}
		}

		$this->preprocessData('com_churchdirectory.member', $data);

		return $data;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   ChurchDirectoryTableMember  $table  ?
	 *
	 * @return    void
	 *
	 * @since    1.7.0
	 */
	protected function prepareTable($table)
	{
		$date = JFactory::getDate();

		$table->name  = htmlspecialchars_decode($table->name, ENT_QUOTES);
		$table->alias = JApplicationHelper::stringURLSafe($table->alias);

		$table->generateAlias();

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
				$db->setQuery('SELECT MAX(ordering) FROM `#__churchdirectory_details`');
				$max = $db->loadResult();

				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   ChurchDirectoryTableMember  $table  A record object.
	 *
	 * @return    array    An array of conditions to add to add to ordering queries.
	 *
	 * @since    1.7.0
	 */
	protected function getReorderConditions($table)
	{
		$condition   = array();
		$condition[] = 'catid = ' . (int) $table->catid;

		return $condition;
	}

	/**
	 * Method to toggle the featured setting of members.
	 *
	 * @param   array  $pks    The ids of the items to toggle.
	 * @param   int    $value  The value to toggle to.
	 *
	 * @throws  string  errors
	 * @throws  string  errors
	 *
	 * @return  boolean    True on success.
	 *
	 * @since    1.7.0
	 */
	public function featured($pks, $value = 0)
	{
		// Sanitize the ids.
		$pks = (array) $pks;
		Joomla\Utilities\ArrayHelper::toInteger($pks);

		if (empty($pks))
		{
			$this->setError(JText::_('COM_CHURCHDIRECTORY_NO_ITEM_SELECTED'));

			return false;
		}

		$table = $this->getTable();

		$db = $this->getDbo();

		$db->setQuery(
			'UPDATE #__churchdirectory_details AS a' .
				' SET a.featured = ' . (int) $value .
				' WHERE a.id IN (' . implode(',', $pks) . ')'
		);

		if (!$db->execute())
		{
			return false;
		}

		$table->reorder();

		// Clean component's cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Custom clean the cache of com_churchdirectory and churchdirectory modules
	 *
	 * @param   string  $group      ?
	 * @param   int     $client_id  ?
	 *
	 * @since    1.6
	 *
	 * @return void;
	 */
	protected function cleanCache($group = null, $client_id = 0)
	{
		parent::cleanCache('com_churchdirectory');
		parent::cleanCache('mod_birthdayanniversary');
	}
}
