<?php

/**
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
//No Direct Access
defined('_JEXEC') or die;

function getPosition($positions) {

    $db =& JFactory::getDBO();
    $query = "select name
             FROM #__churchdirectory_position
    WHERE ".$db->nameQuote('id')." = ".$db->quote($positions).";
            ";
    $db->setQuery($query);
    $name =  $db->loadResult();
    return $name;
}