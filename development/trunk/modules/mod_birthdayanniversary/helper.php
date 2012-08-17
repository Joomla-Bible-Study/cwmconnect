<?php

/**
 * Helper for ChurchDirectory Birthday & Anniversary Display.
 * @package		ChurchDirectory.Site
 * @subpackage	mod_birthdayanniversary
 * @copyright	Copyright (C) 2012
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

require_once JPATH_SITE . '/components/com_churchdirectory/helpers/route.php';

JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_churchdirectory/models', 'ChurchDirectoryModel');

/**
 * helper for Birthdy Anniversary Display
 * @package ChurchDirectory.Site
 * @subpackage mod_birthdayanniversary
 * @since 1.7.2
 */
class modBirthdayAnniversaryHelper {

    /**
     * Get Birthdays for This Month
     * @param array $params
     * @return array
     */
    static function getBirthdays($params) {
        $db = JFactory::getDbo();
        $results = FALSE;
        $query = "SELECT * FROM " . $db->nameQuote('#__churchdirectory_details') . " ORDER BY MONTH(birthdate) DESC";
        $records = modBirthdayAnniversaryHelper::performDB($query);
        foreach ($records as $record):
            if ($record->birthdate !== '0000-00-00'):
                list($byear, $bmonth, $bday) = explode('-', $record->birthdate);
                if ($bmonth === date('m')):
                    $results[] = array('name' => $record->name, 'id' => $record->id, 'day' => $bday);
                endif;
            endif;
        endforeach;
        return $results;
    }

    /**
     * Get Anniversarys for This Month
     * @param array $params
     * @return array
     */
    static function getAnniversary($params) {
        $db = JFactory::getDbo();
        $results = FALSE;
        $query = "SELECT * FROM " . $db->nameQuote('#__churchdirectory_details') . " ORDER BY MONTH(anniversary) DESC";
        $records = modBirthdayAnniversaryHelper::performDB($query);
        foreach ($records as $record):
            if ($record->anniversary !== '0000-00-00'):
                list($byear, $bmonth, $bday) = explode('-', $record->anniversary);
                $tmonth = date('m');
                dump($bmonth, 'bMonth');
                dump($tmonth, 'This Month');
                if ($bmonth === date('m')):
                    $results[] = array('name' => $record->name, 'id' => $record->id, 'day' => $bday);
                endif;
            endif;
        endforeach;
        return $results;
    }

    /**
     * Performs a database query
     * @param $query is a Joomla ready query
     * @return results
     */
    protected static function performDB($query) {
        if (!$query) {
            return false;
        }
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $db->query();
        if ($db->getErrorNum() != 0) {
            return $db->stderr(true);
        } else {
            return $db->loadObjectList();
        }
    }

    /**
     * Convert a stdClass to an Array.
     * @param stdClass $Class
     * @return array
     */
    static public function object_to_array(stdClass $Class) {
        # Typecast to (array) automatically converts stdClass -> array.
        $Class = (array) $Class;

        # Iterate through the former properties looking for any stdClass properties.
        # Recursively apply (array).
        foreach ($Class as $key => $value) {
            if (is_object($value) && get_class($value) === 'stdClass') {
                $Class[$key] = self::object_to_array($value);
            }
        }
        return $Class;
    }

}