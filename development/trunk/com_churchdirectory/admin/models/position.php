<?php

/**
 * @version		$Id: position.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Item Model for a Contact.
 *
 * @package	com_churchdirectory
 * @since		1.7.0
 */
class ChurchDirectoryModelPosition extends JModelAdmin {

    /**
     * Method to test whether a record can be deleted.
     *
     * @param	object	$record	A record object.
     *
     * @return	boolean	True if allowed to delete the record. Defaults to the permission set in the component.
     * @since	1.7.0
     */
    protected function canDelete($record) {
        if (!empty($record->id)) {
            if ($record->published != -2) {
                return;
            }
            $user = JFactory::getUser();
            return $user->authorise('core.delete');
        }
    }

    /**
     * Method to test whether a record can have its state edited.
     *
     * @param	object	$record	A record object.
     *
     * @return	boolean	True if allowed to change the state of the record. Defaults to the permission set in the component.
     * @since	1.7.0
     */
    protected function canEditState($record) {
        $user = JFactory::getUser();
        return parent::canEditState($record);
    }

    /**
     * Returns a Table object, always creating it
     *
     * @param	type	$type	The table type to instantiate
     * @param	string	$prefix	A prefix for the table class name. Optional.
     * @param	array	$config	Configuration array for model. Optional.
     *
     * @return	JTable	A database object
     * @since	1.7.0
     */
    public function getTable($type = 'Position', $prefix = 'ChurchDirectoryTable', $config = array()) {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Method to get the row form.
     *
     * @param	array	$data		Data for the form.
     * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
     *
     * @return	mixed	A JForm object on success, false on failure
     * @since	1.7.0
     */
    public function getForm($data = array(), $loadData = true) {
        jimport('joomla.form.form');
        JForm::addFieldPath('JPATH_ADMINISTRATOR/components/com_users/models/fields');

        // Get the form.
        $form = $this->loadForm('com_churchdirectory.position', 'position', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }

        // Modify the form based on access controls.
        if (!$this->canEditState((object) $data)) {
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
     * Method to get a single record.
     *
     * @param	integer	$pk	The id of the primary key.
     *
     * @return	mixed	Object on success, false on failure.
     * @since	1.7.0
     */
    public function getItem($pk = null) {
        return parent::getItem($pk);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return	mixed	The data for the form.
     * @since	1.7.0
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_churchdirectory.edit.position.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Prepare and sanitise the table prior to saving.
     *
     * @param	JTable	$table
     *
     * @return	void
     * @since	1.7.0
     */
    protected function prepareTable(&$table) {
        jimport('joomla.filter.output');
        $date = JFactory::getDate();
        $user = JFactory::getUser();

        $table->name = htmlspecialchars_decode($table->name, ENT_QUOTES);
        $table->alias = JApplication::stringURLSafe($table->alias);

        if (empty($table->alias)) {
            $table->alias = JApplication::stringURLSafe($table->name);
        }

        if (empty($table->id)) {
            // Set the values
            //$table->created	= $date->toMySQL();
            // Set ordering to the last item if not set
            if (empty($table->ordering)) {
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
     * @param string $id
     * @return array
     * @since 1.7.1
     */
    public function getMembers($id) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('members.con_position, members.name, members.id');
        $query->from('#__churchdirectory_details AS members');
        $query->where('con_position <> ""');
        $query->order('members.lname DESC');

        $db->setQuery($query->__toString());
        $positions = $db->loadObjectList();
        var_dump($positions);
        foreach ($positions as $p):
            $position = new stdClass();
            $position->name = $p->name;
            $position->con_position = explode(',', $p->con_position);
            $position->id = $p->id;
        endforeach;

        var_dump($position);
        $key = array_search($id, $position->con_position);
        if ($key >= '0'):
            $results = $position;
        endif;

        return $results;
    }

    /**
     * A protected method to get a set of ordering conditions.
     *
     * @param	JTable	$table	A record object.
     *
     * @return	array	An array of conditions to add to add to ordering queries.
     * @since	1.7.0
     */
    protected function getReorderConditions($table) {
        $condition = array();

        return $condition;
    }

}
