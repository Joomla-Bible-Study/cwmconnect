<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Get Position
 *
 * @param   int  $con_position  ID of Position
 *
 * @return object
 */
function getPosition($con_position)
{
	$i         = 0;
	$positions = array();
	$results   = null;
	$position  = null;
	$db        = JFactory::getDBO();

	if (strstr($con_position, ','))
	{
		$ids = explode(',', $con_position);

		foreach ($ids AS $id)
		{
			$query = $db->getQuery(true);

			$query->select('id, name');
			$query->from('#__churchdirectory_position');
			$query->where('id = ' . $id);

			$db->setQuery($query);
			$position      = $db->loadObject();
			$positions[$i] = $position;
			$i++;
		}
	}
	elseif ($con_position != '-1')
	{
		$query = $db->getQuery(true);

		$query->select('position.id, position.name');
		$query->from('#__churchdirectory_position AS position');
		$query->where('position.id = ' . $con_position);

		$db->setQuery($query->__toString());
		$position      = $db->loadObject();
		$positions[$i] = $position;
	}
	$n  = count($position);
	$pi = '1';

	foreach ($positions AS $position)
	{
		if ($position)
		{
			if ($n != $pi)
			{
				$results .= $position->name;
				$results .= '<br />';
			}
			else
			{
				$results .= $position->name;
			}
			$pi++;
		}
	}

	return $results;
}
