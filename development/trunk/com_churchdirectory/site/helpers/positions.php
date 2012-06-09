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
    $query->from('#__churchdirectory_position AS position');
    $query->where('position.id = ' . $id);

    $db->setQuery($query->__toString());
    $positions = $db->loadObjectList();
    return $positions;
}