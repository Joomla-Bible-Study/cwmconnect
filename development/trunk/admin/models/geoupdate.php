<?php

/*
 * @version             $Id: default.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die;

jimport('joomla.application.component.model');
jimport('joomla.client.ftp');

class ChurchDirectoryModelGeoUpdate extends JModel {

    /** @var float The time the process started */
    private $startTime = null;

    /**
     * Returns the current timestampt in decimal seconds
     */
    private function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * Starts or resets the internal timer
     */
    private function resetTimer() {
        $this->startTime = $this->microtime_float();
    }

    /**
     * Makes sure that no more than 3 seconds since the start of the timer have
     * elapsed
     * @return bool
     */
    private function haveEnoughTime() {
        $now = $this->microtime_float();
        $elapsed = abs($now - $this->startTime);
        return $elapsed < 3;
    }

    /**
     * Finds all tables using the current site's prefix
     * @return array
     */
    public function getListQuery() {

        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select($this->getState('list.select', 'cd.*'));
        $query->from('`#__churchdirectory_details` AS cd');
        $query->order('cd.id');

        return $query;
    }

    public function update($fromTable = null) {
        $this->resetTimer();
        $tables = $this->getListQuery();

        $db = $this->getDBO();

        while ($geocode_pending) {


            // Finally, optimize
            $db->setQuery('GEO UPDATE ' . $db->nameQuote($table));
            $db->query();
        }

        if (!count($tables))
            return '';

        return $table;
    }

    public function purgeSessions() {
        $db = $this->getDBO();

        $db->setQuery('OPTIMIZE TABLE ' . $db->nameQuote('#__session'));
        $db->query();
    }

}