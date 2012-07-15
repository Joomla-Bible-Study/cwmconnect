<?php

/**
 * @package	ChurchDirectory.Admin
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

class ChurchDirectoryTableFamilyUnit extends JTable {

    /**
     * Constructor
     *
     * @param object Database connector object
     * @since 1.7.0
     */
    public function __construct(& $db) {
        parent::__construct('#__churchdirectory_familyunit', 'id', $db);
    }

    /**
     * Overloaded bind function
     *
     * @param	array		Named array
     * @return	null|string	null is operation was satisfactory, otherwise returns an error
     * @since	1.7.0
     */
    public function bind($array, $ignore = '') {
        if (isset($array['params']) && is_array($array['params'])) {
            $registry = new JRegistry();
            $registry->loadArray($array['params']);
            $array['params'] = (string) $registry;
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Stores a contact
     *
     * @param	boolean	True to update fields even if they are null.
     * @return	boolean	True on success, false on failure.
     * @since	1.7.0
     */
    public function store($updateNulls = false) {
        // Transform the params field
        if (is_array($this->params)) {
            $registry = new JRegistry();
            $registry->loadArray($this->params);
            $this->params = (string) $registry;
        }

        $date = JFactory::getDate();
        $user = JFactory::getUser();
        if ($this->id) {
            // Existing item
            $this->modified = $date->toSql();
            $this->modified_by = $user->get('id');
        } else {
            // New newsfeed. A feed created and created_by field can be set by the user,
            // so we don't touch either of these if they are set.
            if (!intval($this->created)) {
                $this->created = $date->toSql();
            }
            if (empty($this->created_by)) {
                $this->created_by = $user->get('id');
            }
        }

        // Attempt to store the data.
        return parent::store($updateNulls);
    }

    /**
     * Overloaded check function
     *
     * @return boolean
     * @see JTable::check
     * @since 1.7.0
     */
    function check() {

        if (JFilterInput::checkAttribute(array('href', $this->webpage))) {
            $this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_URL'));
            return false;
        }

        // check for http, https, ftp on webpage
        if ((strlen($this->webpage) > 0)
                && (stripos($this->webpage, 'http://') === false)
                && (stripos($this->webpage, 'https://') === false)
                && (stripos($this->webpage, 'ftp://') === false)) {
            $this->webpage = 'http://' . $this->webpage;
        }

        /** check for valid name */
        if (trim($this->name) == '') {
            $this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_PROVIDE_VALID_NAME'));
            return false;
        }
        /** check for existing name */
        $query = 'SELECT id FROM #__churchdirectory_familyunit WHERE name = ' . $this->_db->Quote($this->name);
        $this->_db->setQuery($query);

        $xid = intval($this->_db->loadResult());
        if ($xid && $xid != intval($this->id)) {
            $this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_SAME_NAME'));
            return false;
        }

        if (empty($this->alias)) {
            $this->alias = $this->name;
        }
        $this->alias = JApplication::stringURLSafe($this->alias);
        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = JFactory::getDate()->format("Y-m-d-H-i-s");
        }

        // Check the publish down date is not earlier than publish up.
        if (intval($this->publish_down) > 0 && $this->publish_down < $this->publish_up) {
            // Swap the dates.
            $temp = $this->publish_up;
            $this->publish_up = $this->publish_down;
            $this->publish_down = $temp;
        }

        return true;
    }

}
