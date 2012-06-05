<?php

/**
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
//No Direct Access
defined('_JEXEC') or die;

function getPosition($id) {

    $db = JFactory::getDBO();
    $query = $db->getQuery(true);

    $query->select('position.id, position.name');
    $query->from('#__churchdirectory_details_ps AS details_ps');

    $query->join('LEFT', '#__churchdirectory_position AS position ON position.id = details_ps.contact_id');
    $query->where('details_ps.contact_id = '. $id);
    
    $db->setQuery($query->__toString());
    $positions = $db->loadObjectList();
    return json_encode($positions);
}