<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Class for Rendering out Page Elements
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.5
 */
class RenderHelper
{
	public $children;

	/**
	 * Get Position
	 *
	 * @param   int $con_position  ID of Position
	 *
	 * @return object
	 */
	public function getPosition ($con_position)
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
		$n  = count($positions);
		$pi = '1';

		foreach ($positions AS $position)
		{
			if ($position)
			{
				if ($n != $pi)
				{
					$results .= $position->name;
					$results .= '</dd><dd>';
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

	/**
	 * Get Family Members Build
	 *
	 * @param   object $funit_id  ID of Primary Record
	 *
	 * @return string
	 */
	public function getFamilyMembersPage ($funit_id)
	{

		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('members.*');
		$query->from('#__churchdirectory_details AS members');
		$query->where('members.funitid = ' . (int) $funit_id);
		$query->order('members.name DESC');

		$db->setQuery($query->__toString());
		$items = $db->loadObjectList();

		// Convert the params field into an object, saving original in _params
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = & $items[$i];

			if (!isset($this->_params))
			{
				$params = new JRegistry;
				$params->loadString($item->params);
				$item->params = $params;
			}
			if (!isset($this->_attribs))
			{
				$params = new JRegistry;
				$params->loadString($item->attribs);
				$item->attribs = $params;
			}
		}

		return $items;
	}

	/**
	 * Calculate rows into span's
	 *
	 * @param   int $rows_per_page  Number of Rows we want to see.
	 *
	 * @return int
	 */
	public function rowWidth ($rows_per_page)
	{
		$results = 12;

		if ($rows_per_page == 2)
		{
			/* span6 */
			$results = 6;
		}
		elseif ($rows_per_page == 3)
		{
			/* span4 */
			$results = 4;
		}
		elseif ($rows_per_page == 4)
		{
			/* span2 */
			$results = 2;
		}

		return $results;
	}

	/**
	 * Ror passing records out to put then in order and not repeat the records.
	 *
	 * @param   array $args  Array of Items to group
	 *
	 * @return array
	 */
	public static function groupit ($args)
	{
		$items = null;
		$field = null;
		extract($args);
		$result = array();

		foreach ($items as $item)
		{
			if (!empty($item->$field))
			{
				$key = $item->$field;
			}
			else
			{
				$key = 'nomatch';
			}
			if (array_key_exists($key, $result))
			{
				$result[$key][] = $item;
			}
			else
			{
				$result[$key]   = array();
				$result[$key][] = $item;
			}
		}

		return $result;
	}

	/**
	 * Compute lastname, firstname and middlename
	 *
	 * @param    string  $name Name of member
	 *
	 * @return stdClass
	 */
	public function getName($name)
	{
		// Compute lastname, firstname and middlename
		$name = trim($name);

		/* "Lastname, Firstname Midlename" format support
		 e.g. "de Gaulle, Charles" */
		$namearray = explode(',', $name);
		$middlename = '';

		if (count($namearray) > 1)
		{
			$lastname         = $namearray[0];
			$card_name        = $lastname;
			$name_and_midname = trim($namearray[1]);
			$firstname        = '';

			if (!empty($name_and_midname))
			{
				$namearray = explode(' ', $name_and_midname);

				$firstname  = $namearray[0];
				$middlename = (count($namearray) > 1) ? $namearray[1] : '';
				$card_name  = $firstname . ' ' . ($middlename ? $middlename . ' ' : '') . $card_name;
			}
		}
		// "Firstname Middlename Lastname" format support
		else
		{
			$namearray = explode(' ', $name);

			$middlename = (count($namearray) > 2) ? $namearray[1] : '';
			$firstname  = array_shift($namearray);
			$lastname   = count($namearray) ? end($namearray) : '';
			$card_name  = $firstname . ($middlename ? ' ' . $middlename : '') . ($lastname ? ' ' . $lastname : '');
		}

		$result = new stdClass;
		$result->firstname = $firstname;
		$result->middlename = $middlename;
		$result->firstname = $firstname;
		$result->card_name = $card_name;

		return $result;
	}
}
