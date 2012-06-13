<?php

/**
 * @package	ChurchDirectory.Admin
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

class ChurchDirectoryTableMember extends JTable {

    /**
     * Constructor
     *
     * @param object Database connector object
     * @since 1.0
     */
    public function __construct(& $db) {
        parent::__construct('#__churchdirectory_details', 'id', $db);
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

        if (isset($array['attribs']) && is_array($array['attribs'])) {
            $registry = new JRegistry();
            $registry->loadArray($array['attribs']);
            $array['attribs'] = (string) $registry;
        }

        if (isset($array['metadata']) && is_array($array['metadata'])) {
            $registry = new JRegistry();
            $registry->loadArray($array['metadata']);
            $array['metadata'] = (string) $registry;
        }
        if (key_exists( 'con_position', $array ) && is_array( $array['con_position'] )) {
	        $array['con_position'] = implode( ',', $array['con_position'] );
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
        // Transform the params field
        if (is_array($this->attribs)) {
            $registry = new JRegistry();
            $registry->loadArray($this->attribs);
            $this->attribs = (string) $registry;
        }

        $date = JFactory::getDate();
        $user = JFactory::getUser();
        if ($this->id) {
            // Existing item
            $this->modified = $date->toMySQL();
            $this->modified_by = $user->get('id');
        } else {
            // New newsfeed. A feed created and created_by field can be set by the user,
            // so we don't touch either of these if they are set.
            if (!intval($this->created)) {
                $this->created = $date->toMySQL();
            }
            if (empty($this->created_by)) {
                $this->created_by = $user->get('id');
            }
        }
        // Verify that the alias is unique
        $table = JTable::getInstance('Member', 'ChurchDirectoryTable');
        if ($table->load(array('alias' => $this->alias, 'catid' => $this->catid)) && ($table->id != $this->id || $this->id == 0)) {
            $this->setError(JText::_('COM_CHURCHDIRECTORY_ERROR_UNIQUE_ALIAS'));
            return false;
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
        $this->default_con = intval($this->default_con);

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
        $query = 'SELECT id FROM #__churchdirectory_details WHERE name = ' . $this->_db->Quote($this->name) . ' AND catid = ' . (int) $this->catid;
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
        /** check for valid category */
        if (trim($this->catid) == '') {
            $this->setError(JText::_('COM_CHURCHDIRECTORY_WARNING_CATEGORY'));
            return false;
        }

        // Check the publish down date is not earlier than publish up.
        if (intval($this->publish_down) > 0 && $this->publish_down < $this->publish_up) {
            // Swap the dates.
            $temp = $this->publish_up;
            $this->publish_up = $this->publish_down;
            $this->publish_down = $temp;
        }

        return true;
        // clean up keywords -- eliminate extra spaces between phrases
        // and cr (\r) and lf (\n) characters from string
        if (!empty($this->metakey)) {
            // only process if not empty
            $bad_characters = array("\n", "\r", "\"", "<", ">"); // array of characters to remove
            $after_clean = JString::str_ireplace($bad_characters, "", $this->metakey); // remove bad characters
            $keys = explode(',', $after_clean); // create array using commas as delimiter
            $clean_keys = array();
            foreach ($keys as $key) {
                if (trim($key)) {  // ignore blank keywords
                    $clean_keys[] = trim($key);
                }
            }
            $this->metakey = implode(", ", $clean_keys); // put array back together delimited by ", "
        }

        // clean up description -- eliminate quotes and <> brackets
        if (!empty($this->metadesc)) {
            // only process if not empty
            $bad_characters = array("\"", "<", ">");
            $this->metadesc = JString::str_ireplace($bad_characters, "", $this->metadesc);
        }
        return true;
    }

    public function load($pk = null, $reset = true) {
        if (parent::load($pk, $reset)) {
            // Convert the params field to a registry.
            $params = new JRegistry;
            $params->loadJSON($this->params);
            $this->params = $params;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method to compute the default name of the asset.
     * The default name is in the form `table_name.id`
     * where id is the value of the primary key of the table.
     *
     * @return      string
     * @since       1.6
     */
    protected function _getAssetName() {
        $k = $this->_tbl_key;
        return 'com_churchdirectory.member.' . (int) $this->$k;
    }

    /**
     * Method to return the title to use for the asset table.
     *
     * @return      string
     * @since       1.6
     */
    protected function _getAssetTitle() {
        $title = $this->name;
        return $title;
    }

    /**
     * Get the parent asset id for the record
     *
     * @return      int
     * @since       1.6
     */
    protected function _getAssetParentId($table=null, $id=null) {
        $asset = JTable::getInstance('Asset');
        $asset->loadByName('com_churchdirectory');
        return $asset->id;
    }

}
