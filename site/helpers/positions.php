<?php

/**
 * Helper for positions
 *
 * @package             ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license             GNU General Public License version 2 or later; see LICENSE.txt
 */
//No Direct Access
defined('_JEXEC') or die;

/**
 * Get Position
 *
 * @param int $id
 *
 * @return object
 */
function getPosition($con_position)
{
	$i = 0;
	$positions = array();
	$results = null;
	if (strstr($con_position, ',')) {
		$ids = explode(',', $con_position);
		foreach ($ids AS $id):
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);

			$query->select('position.id, position.name');
			$query->from('#__churchdirectory_position AS position');
			$query->where('position.id = ' . $id);

			$db->setQuery($query->__toString());
			$position = $db->loadObject();
			$positions[$i] = $position;
			$i++;
		endforeach;
	} else {
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('position.id, position.name');
		$query->from('#__churchdirectory_position AS position');
		$query->where('position.id = ' . $con_position);

		$db->setQuery($query->__toString());
		$position = $db->loadObject();
		$positions[$i] = $position;
	}
	foreach ($positions AS $position):
		$results .= $position->name;
		$results .= '<br />';
	endforeach;

	return $results;
}