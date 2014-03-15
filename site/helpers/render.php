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

	protected $burthday;

	protected $burthyear;

	protected $burthmonth;

	protected $byear;

	protected $bmonth;

	protected $bday;

	/**
	 * Get Position
	 *
	 * @param   string     $con_position  ID of Position
	 * @param   bool       $getint        ID of Position
	 * @param   JRegistry  $params        ID of Position
	 *
	 * @return string|bool
	 */
	public function getPosition($con_position, $getint = false, $params = null)
	{
		$i         = 0;
		$positions = array();
		$results   = '';
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
		elseif ($con_position != '-1' && $con_position != '0' && $con_position != '')
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

		if (!$getint)
		{
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
		}
		else
		{
			foreach ($positions AS $position)
			{
				$teamleaders = $params->get('teamleaders', '');

				if ($position->id == $teamleaders)
				{
					$results = true;
				}
			}

		}

		return $results;
	}

	/**
	 * Get Family Members Build
	 *
	 * @param   int     $fu_id  ID of Family unit
	 * @param   string  $fm     ID the Family Position that you want to return.
	 *
	 * @return array  Array of family members
	 */
	public function getFamilyMembers($fu_id, $fm = '2')
	{

		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('members.*');
		$query->from('#__churchdirectory_details AS members');
		$query->where('members.funitid = ' . (int) $fu_id);
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
			if ($item->attribs->get('familypostion') != $fm)
			{
				unset($items[$i]);
			}
		}

		return $items;
	}

	/**
	 * Get Children from families
	 *
	 * @param   int|array  $families  Int if family id and Array of family members
	 *
	 * @return string HTML string
	 */
	public function getChildren($families)
	{
		if (is_int($families))
		{
			$families = self::getFamilyMembers($families);
		}
		$n2   = count($families);
		$i2   = $n2;
		$name = '';

		foreach ($families as $member)
		{
			if (($n2 == $i2 && $n2 < 2) || ($n2 == 2 && $n2 == $i2))
			{
				$name .= self::getMemberStatus($member) . ' ';
			}
			elseif ($n2 > 2 && $i2 > 1)
			{
				$name .= self::getMemberStatus($member) . ', ';
			}
			elseif ($i2 == 1 && $n2 >= 2)
			{
				$name .= '&amp; ' . self::getMemberStatus($member);
			}
			$i2--;
		}

		return $name;
	}

	/**
	 * Get Spouse of Member
	 *
	 * @param   int  $fu_id            ID of family unit
	 * @param   int  $family_position  ID of members family position.
	 *
	 * @return string
	 */
	public function getSpouse($fu_id, $family_position)
	{
		if($family_position == 1){
			$fm = 0;
		}else{
			$fm = 1;
		}
		$members = self::getFamilyMembers($fu_id, $fm);
		$spouse  = null;

		foreach ($members as $member)
		{
			$spouse = self::getMemberStatus($member);
		}

		return $spouse;
	}

	/**
	 * Get Member Status
	 *
	 * @param   object  $member  Member info
	 *
	 * @return string HTML string returned
	 */
	public function getMemberStatus($member)
	{
		$mstatus = null;

		if ($member->mstatus == '0') // Active Member
		{
			$mstatus = '<a href="index.php?option=com_churchdirectory&view=member&id= ' . $member->id . '"><span><b>' . $member->name . '</b></span></a>';
		}
		elseif ($member->mstatus == '1') // Inactive Member
		{
			$mstatus = '<a href="index.php?option=com_churchdirectory&view=member&id= ' . $member->id . '"><span><b>' . $member->name . '</b></span></a>';
		}
		elseif ($member->mstatus == '2') // Active Attendee
		{
			$mstatus = '<a href="index.php?option=com_churchdirectory&view=member&id= ' . $member->id . '"><span>( ' . $member->name . ' )</span></a>';
		}
		elseif ($member->mstatus == '3') // None Member
		{
			$mstatus = '<a href="index.php?option=com_churchdirectory&view=member&id= ' . $member->id . '"><span style="color: gray;">( ' . $member->name . ' )</span></a>';
		}

		return $mstatus;
	}

	/**
	 * Calculate rows into span's
	 *
	 * @param   int  $rows_per_page  Number of Rows we want to see.
	 *
	 * @return int
	 */
	public function rowWidth($rows_per_page)
	{
		return 12/$rows_per_page;
	}

	/**
	 * Ror passing records out to put then in order and not repeat the records.
	 *
	 * @param   array  $args  Array of Items to group
	 *
	 * @return array
	 */
	public static function groupit($args)
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
	 * Compute last name, first name and middle name
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
		$namearray  = explode(',', $name);
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

		$result             = new stdClass;
		$result->firstname  = $firstname;
		$result->middlename = $middlename;
		$result->firstname  = $firstname;
		$result->card_name  = $card_name;

		return $result;
	}

	/**
	 * Get Birthdays for This Month
	 *
	 * @param   JRegistry  $params  Model Params
	 *
	 * @return array
	 */
	public function getBirthdays($params)
	{
		$user   = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		$db      = JFactory::getDbo();
		$results = false;
		$query   = $db->getQuery(true);

		// Select required fields from the categories.
		// Sqlsrv changes
		$case_when = ' CASE WHEN ';
		$case_when .= $query->charLength('a.alias', '!=', '0');
		$case_when .= ' THEN ';
		$a_id = $query->castAsChar('a.id');
		$case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
		$case_when .= ' ELSE ';
		$case_when .= $a_id . ' END as slug';

		$case_when1 = ' CASE WHEN ';
		$case_when1 .= $query->charLength('c.alias', '!=', '0');
		$case_when1 .= ' THEN ';
		$c_id = $query->castAsChar('c.id');
		$case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
		$case_when1 .= ' ELSE ';
		$case_when1 .= $c_id . ' END as catslug';
		$query->select('a.*' . ',' . $case_when . ',' . $case_when1);
		$query->from($db->quoteName('#__churchdirectory_details') . ' AS a');
		$query->where('a.published = 1');

		// Join on category table.
		$query->select('c.title AS category_title, c.params AS category_params, c.alias AS category_alias, c.access AS category_access');
		$query->where('a.access IN (' . $groups . ')');
		$query->join('INNER', '#__categories AS c ON c.id = a.catid');
		$query->where('c.access IN (' . $groups . ')');

		// Join to check for category published state in parent categories up the tree
		// TODO need to redo the Query;
		$query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the churchdirectory state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter of birthdates to show
		$date = $params->get('month', date('m'));
		$query->where('MONTH(a.birthdate) = ' . $date);

		$query->where('a.birthdate != "0000-00-00"')
			->order('a.birthdate DESC');
		$db->setQuery($query);
		$records = $db->loadObjectList();

		foreach ($records as $record)
		{
			list($this->burthyear, $this->burthmonth, $this->burthday) = explode('-', $record->birthdate);
			$results[] = array('name' => $record->name, 'id' => $record->id, 'day' => $this->burthday, 'access' => $record->access);
		}

		return $results;
	}

	/**
	 * Get Anniversary's for this Month
	 *
	 * @param   JRegistry  $params  Model Params
	 *
	 * @return array
	 */
	public function getAnniversary($params)
	{
		$user    = JFactory::getUser();
		$groups  = implode(',', $user->getAuthorisedViewLevels());

		$db      = JFactory::getDbo();
		$results = false;
		$query   = $db->getQuery(true);

		// Select required fields from the categories.
		// Sqlsrv changes
		$case_when = ' CASE WHEN ';
		$case_when .= $query->charLength('a.alias', '!=', '0');
		$case_when .= ' THEN ';
		$a_id = $query->castAsChar('a.id');
		$case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
		$case_when .= ' ELSE ';
		$case_when .= $a_id . ' END as slug';

		$case_when1 = ' CASE WHEN ';
		$case_when1 .= $query->charLength('c.alias', '!=', '0');
		$case_when1 .= ' THEN ';
		$c_id = $query->castAsChar('c.id');
		$case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
		$case_when1 .= ' ELSE ';
		$case_when1 .= $c_id . ' END as catslug';
		$query->select('a.*' . ',' . $case_when . ',' . $case_when1);
		$query->from($db->quoteName('#__churchdirectory_details') . ' AS a');
		$query->where('a.published = 1');

		// Join on category table.
		$query->select('c.title AS category_title, c.params AS category_params, c.alias AS category_alias, c.access AS category_access');
		$query->where('a.access IN (' . $groups . ')');
		$query->join('INNER', '#__categories AS c ON c.id = a.catid');
		$query->where('c.access IN (' . $groups . ')');

		// Join to check for category published state in parent categories up the tree
		// TODO need to redo the Query;
		$query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the churchdirectory state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter of birthdates to show
		$date = $params->get('month', date('m'));
		$query->where('MONTH(a.anniversary) = ' . $date);

		$query->where('a.anniversary != "0000-00-00"')
			->order('a.anniversary DESC');
		$db->setQuery($query);
		$records = $db->loadObjectList();

		foreach ($records as $record)
		{
			list($this->byear, $this->bmonth, $this->bday) = explode('-', $record->anniversary);
			$results[] = array('name' => $record->name, 'id' => $record->id, 'day' => $this->bday, 'access' => $record->access);
		}

		return $results;
	}
}
