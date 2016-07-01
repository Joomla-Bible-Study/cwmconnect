<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
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

	protected $f_id;

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
		$db        = JFactory::getDbo();

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

		$db    = JFactory::getDbo();
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
				$params = new Registry;
				$params->loadString($item->params);
				$item->params = $params;
			}
			if (!isset($this->_attribs))
			{
				$params = new Registry;
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
	 * @param   bool       $from      If from Admin or Site(True)
	 *
	 * @return string HTML string
	 */
	public function getChildren($families, $from = false)
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
				$name .= self::getMemberStatus($member, $from) . ' ';
			}
			elseif ($n2 > 2 && $i2 > 1)
			{
				$name .= self::getMemberStatus($member, $from) . ', ';
			}
			elseif ($i2 == 1 && $n2 >= 2)
			{
				$name .= '&amp; ' . self::getMemberStatus($member, $from);
			}
			$i2--;
		}

		return $name;
	}

	/**
	 * Get Spouse of Member
	 *
	 * @param   int   $fu_id            ID of family unit
	 * @param   int   $family_position  ID of members family position.
	 * @param   bool  $from             If from Admin or Site(True)
	 *
	 * @return string
	 */
	public function getSpouse($fu_id, $family_position, $from = false)
	{
		if ($family_position == 1)
		{
			$fm = 0;
		}
		else
		{
			$fm = 1;
		}
		$members = self::getFamilyMembers($fu_id, $fm);
		$spouse  = null;

		foreach ($members as $member)
		{
			$spouse = self::getMemberStatus($member, $from);
		}

		return $spouse;
	}

	/**
	 * Get Member Status
	 *
	 * @param   object  $member  Member info
	 * @param   bool    $from    If from Admin or Site(True)
	 *
	 * @return string HTML string returned
	 */
	public function getMemberStatus($member, $from = false)
	{
		$mstatus = null;

		if ($member->mstatus == '0') // Active Member
		{
			if ($from)
			{
				$mstatus = $member->name;
			}
			else
			{
				$mstatus = '<a href="index.php?option=com_churchdirectory&view=member&id=' . (int) $member->id . '">' . $member->name . '</a>';
			}
		}
		elseif ($member->mstatus == '1') // Inactive Member
		{
			if ($from)
			{
				$mstatus = $member->name;
			}
			else
			{
				$mstatus = '<a href="index.php?option=com_churchdirectory&view=member&id=' . (int) $member->id . '">' . $member->name . '</a>';
			}
		}
		elseif ($member->mstatus == '2') // Active Attendee
		{
			if ($from)
			{
				$mstatus = $member->name;
			}
			else
			{
				$mstatus = '<a href="index.php?option=com_churchdirectory&view=member&id=' . (int) $member->id . '">( ' . $member->name . ' )</a>';
			}
		}
		elseif ($member->mstatus == '3') // None Member
		{
			if ($from)
			{
				$mstatus = $member->name;
			}
			else
			{
				$mstatus = '<span style="color: gray;"><a href="index.php?option=com_churchdirectory&view=member&id=' .
						(int) $member->id . '">( ' . $member->name . ' )</a></span>';
			}
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
		return 12 / $rows_per_page;
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
	 * @param   string  $name  Name of member
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

		$query->select('a.*')
			->from($db->qn('#__churchdirectory_details') . ' AS a')
			->where('a.access IN (' . $groups . ')')
			->join('INNER', '#__categories AS c ON c.id = a.catid')
			->where('c.access IN (' . $groups . ')');
		$query->where('a.published = 1');

		// Sqlsrv change... aliased c.published to cat_published
		// Join to check for category published state in parent categories up the tree
		$query->select('c.published as cat_published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT `cat.id` as id FROM `#__categories` AS cat JOIN `#__categories` AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the contact state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';

		// Select state to unpublished if up-path category is unpublished
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter of birthdates to show
		$date = $params->get('month', date('m'));
		if ($date == '0')
		{
			$date = date('m');
		}
		$query->where('MONTH(a.birthdate) = ' . $date);

		$query->where('a.birthdate != "0000-00-00"')
			->order('DAY(a.birthdate) ASC');
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

		$query->select('a.*')
			->from($db->quoteName('#__churchdirectory_details') . ' AS a')
			->where('a.access IN (' . $groups . ')')
			->join('INNER', '#__categories AS c ON c.id = a.catid')
			->where('c.access IN (' . $groups . ')');
		$query->where('a.published = 1');

		// Sqlsrv change... aliased c.published to cat_published
		// Join to check for category published state in parent categories up the tree
		$query->select('c.published as cat_published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT `cat.id` as id FROM `#__categories` AS cat JOIN `#__categories` AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the contact state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';

		// Select state to unpublished if up-path category is unpublished
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Join over Familey info
		$query->select('f.name as f_name, f.id as f_id');
		$query->join('LEFT OUTER', '#__churchdirectory_familyunit as f ON f.id = a.funitid');

		// Filter of birthdates to show
		$date = $params->get('month', date('m'));
		if ($date == '0')
		{
			$date = date('m');
		}
		$query->where('MONTH(a.anniversary) = ' . $date);

		$query->where('a.anniversary != "0000-00-00"')
			->order('DAY(a.anniversary) ASC');
		$db->setQuery($query);
		$records = $db->loadObjectList();

		foreach ($records as $i => $record)
		{
			list($this->byear, $this->bmonth, $this->bday) = explode('-', $record->anniversary);
			if ($record->f_name && $record->f_id != $this->f_id)
			{
				$this->f_id = $record->f_id;
				$results[] = array('name' => $record->f_name, 'id' => $record->f_id, 'day' => $this->bday, 'access' => $record->access);
			}
			elseif (!$record->f_name)
			{
				$results[] = array('name' => $record->name, 'id' => $record->id, 'day' => $this->bday, 'access' => $record->access);
			}
			else
			{
				$this->f_id = null;
				unset($records[$i]);
			}

		}

		return $results;
	}
}
