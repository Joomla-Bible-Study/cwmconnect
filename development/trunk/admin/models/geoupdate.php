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
     * Finds all Contacts
     * @return array
     */
    public function findRecords() {
        $db = & JFactory::getDBO();
        $query = "SELECT id, name, address, suburb, state, postcode, lat, lng , country
        FROM   #__churchdirectory_details
        WHERE  1";
        $db->setQuery($query);
        $ret = $db->loadObjectList();
        return $ret;
    }

    public function update($update = null) {
        $this->resetTimer();
        $base_url = "http://maps.google.com/maps/geo?output=xml"; // . "&key=" . "$key";
        $geocode_pending = true;
        $this->resetTimer();
        $geoupdate = $this->findRecords();
        foreach ($geoupdate AS $row) {
            // Initialize delay in geocode speed
            $delay = 0;
            $geocode_pending = true;

            while ($geocode_pending) {
                // Defining of Rows to look up
                $request_url = $base_url . "&q=" . urlencode("$row->address" . "," . " " . "$row->suburb" . "," . "$row->state" . " " . "$row->postcode" . " " . "$row->country");
                $xml = simplexml_load_file($request_url) or die("url not loading");

                $status = $xml->Response->Status->code;
                if (strcmp($status, "200") == 0) {
                    // Successful geocode
                    $geocode_pending = false;
                    $coordinates = $xml->Response->Placemark->Point->coordinates;
                    $coordinatesSplit = split(",", $coordinates);
                    // Format: Longitude, Latitude, Altitude
                    $ulat = $coordinatesSplit[1];
                    $ulong = $coordinatesSplit[0];

                    $query = sprintf("UPDATE $table " .
                            " SET lat = '%s', lng = '%s'" .
                            " WHERE id = '%s' LIMIT 1;", mysql_real_escape_string($ulat), mysql_real_escape_string($ulong), mysql_real_escape_string($id));
                    $update_result = mysql_query($query);
                    if (!$update_result) {
                        die("Invalid query: " . mysql_error());
                    }
                } else if (strcmp($status, "620") == 0) {
                    // sent geocodes too fast
                    $delay += 100000;
                } else {
                    //failure to geocode
                    $geocode_pending = false;
                    echo "Name: " . $row->name . "<br />";
                    echo $xml . "<br />";
                    echo "Address " . $row->address . "," . " " . "$row->suburb" . "," . "$row->state" . " " . "$row->postcode" . " " . "$row->country" . " failed to geocoded.<br /> ";
                    echo "Received status " . $status . " \n <br /><br />";
                }
                usleep($delay);
            }
        }
    }

    public function purgeSessions() {
        $db = $this->getDBO();
        $db->setQuery('OPTIMIZE TABLE ' . $db->nameQuote('#__session'));
        $db->query();
    }

}