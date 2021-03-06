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
class ChurchDirectoryRenderHelper
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
	 * @param   string    $con_position  ID of Position
	 * @param   bool      $getint        ID of Position
	 * @param   Registry  $params        ID of Position
	 *
	 * @return string|bool
	 *
	 * @since    1.7.5
	 */
	public function getPosition($con_position, $getint = false, $params = null)
	{
		$i         = 0;
		$positions = [];
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
				$query->where('id = ' . (int) $id);

				$db->setQuery($query);
				$position      = $db->loadObject();
				$positions[$i] = $position;
				$i++;
			}
		}
		elseif ($con_position != '-1' && $con_position != '0' && $con_position != '')
		{
			$query = $db->getQuery(true);

			$query->select('id, name');
			$query->from('#__churchdirectory_position');
			$query->where('id = ' . (int) $con_position);

			$db->setQuery($query);
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
						$results .= '&bull; ' . $position->name;
						$results .= '<br />';
					}
					else
					{
						$results .= '&bull; ' . $position->name;
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
	 * @param   int     $fu_id     ID of Family unit
	 * @param   string  $fm        ID the Family Position that you want to return.
	 * @param   bool    $children  If trying to find childern.
	 *
	 * @return array  Array of family members
	 *
	 * @since    1.7.5
	 */
	public function getFamilyMembers($fu_id, $fm = '2', $children = false)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('members.*');
		$query->from('#__churchdirectory_details AS members');
		$query->where('members.funitid = ' . (int) $fu_id);
		$query->order('members.name DESC');

		$db->setQuery($query);
		$items = $db->loadObjectList();

		// Convert the params field into an object, saving original in _params
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = &$items[$i];

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

			if ((int) $item->attribs->get('familypostion') !== $fm && !$children)
			{
				unset($items[$i]);
			}
		}

		return $items;
	}

	/**
	 * Get Children from families
	 *
	 * @param   int|array  $families        Int if family id and Array of family members
	 * @param   bool       $from            If from Admin or Site(True)
	 * @param   string     $oldchildren_rc  Old Childeren Records.
	 *
	 * @return string HTML string
	 *
	 * @since    1.7.5
	 */
	public function getChildren($families, $from = false, $oldchildren_rc = null)
	{
		if (is_int($families))
		{
			$families = self::getFamilyMembers($families, 2, true);
		}

		if (!is_array($families))
		{
			$families = ['0' => $families];
		}

		$n2   = count($families);
		$i2   = $n2;
		$name = '';

		foreach ($families as $i => $member)
		{
			if (isset($member->attribs) && $member->attribs->get('familypostion') == 2)
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
			}

			$i2--;
		}

		if (!empty($name) || !empty($oldchildren_rc))
		{
			if (!empty($name))
			{
				$name = $name . ' ';
			}

			$name = '<span class="jicons-text">' . JText::_('COM_CHURCHDIRECTORY_CHILDREN') . ': </span>' . $name . $oldchildren_rc;
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
	 *
	 * @since    1.7.5
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
	 *
	 * @since    1.7.5
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
	 *
	 * @since    1.7.5
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
	 *
	 * @since    1.7.5
	 */
	public function groupit($args)
	{
		$items = null;
		$field = null;
		$description = null;
		extract($args);
		$result = [];

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
				$result[$key]   = [];
				$result[$key][] = $item;
			}
		}

		ksort($result);

		return $result;
	}

	/**
	 * Compute last name, first name and middle name
	 *
	 * @param   string  $name  Name of member
	 *
	 * @return stdClass
	 *
	 * @since    1.7.5
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
	 * @param   Registry  $params  Model Params
	 *
	 * @return array
	 *
	 * @since    1.7.5
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
			->where('c.access IN (' . $groups . ')')
			->where('a.published = 1');

		// Join to check for category published state in parent categories up the tree
		$query->select('c.published as cat_published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the contact state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';

		// Select state to unpublished if up-path category is unpublished
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter by start and end dates.
		$nullDate = $db->q($db->getNullDate());
		$nowDate  = $db->q(JFactory::getDate()->toSql());

		// Filter by published state.
		$query->where('a.published = ' . 1);
		$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
		$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');

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
			list($get_date, $get_time) = explode(" ", $record->birthdate);
			list($this->byear, $this->bmonth, $this->bday) = explode('-', $get_date);
			$results[] = ['name' => $record->name, 'id' => $record->id, 'day' => $this->bday, 'access' => $record->access];
		}

		return $results;
	}

	/**
	 * Get Anniversary's for this Month
	 *
	 * @param   Registry  $params  Model Params
	 *
	 * @return array
	 *
	 * @since    1.7.5
	 */
	public function getAnniversary($params)
	{
		$user   = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		$db      = JFactory::getDbo();
		$results = [];
		$query   = $db->getQuery(true);

		$query->select('a.*')
			->from($db->quoteName('#__churchdirectory_details') . ' AS a')
			->where('a.access IN (' . $groups . ')')
			->join('INNER', '#__categories AS c ON c.id = a.catid')
			->where('c.access IN (' . $groups . ')');
		$query->where('a.published = 1');

		// Join to check for category published state in parent categories up the tree
		$query->select('c.published as cat_published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the contact state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';

		// Select state to unpublished if up-path category is unpublished
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter by start and end dates.
		$nullDate = $db->q($db->getNullDate());
		$nowDate  = $db->q(JFactory::getDate()->toSql());

		// Filter by published state.
		$query->where('a.published = ' . 1);
		$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
		$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');

		// Join over Familey info
		$query->select('f.name as f_name, f.id as f_id');
		$query->join('LEFT OUTER', '#__churchdirectory_familyunit as f ON f.id = a.funitid');

		// Filter of Anniversary to show
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
			$date = JHtml::_('date', $record->anniversary, JText::_('DATE_FORMAT_LC4'), false);
			list($byear, $bmonth, $bday) = explode('-', $date);

			if ($record->f_name && $record->f_id != $this->f_id)
			{
				$this->f_id = $record->f_id;
				$results[]  = ['name' => $record->f_name, 'id' => $record->f_id, 'day' => $bday, 'access' => $record->access];
			}
			elseif (!$record->f_name)
			{
				$results[] = ['name' => $record->name, 'id' => $record->id, 'day' => $bday, 'access' => $record->access];
			}
			else
			{
				$this->f_id = null;
				unset($records[$i]);
			}
		}

		return $results;
	}

	/**
	 * Render Address
	 *
	 * @param   object    $item    Items to render
	 * @param   Registry  $params  Params
	 *
	 * @return string
	 *
	 * @since 1.7.5
	 */
	public function renderAddress($item, $params)
	{
		$html = '';

		if (($params->get('address_check') > 0)
			&& ($item->address || $item->suburb || $item->state || $item->postcode))
		{
			$html .= '<div class="cd_address">';
			$html .= '<span class="' . $params->get('marker_class') . '">' .
				$params->get('marker_address') .
				'</span>';
		}

		if ($item->address && $params->get('dr_show_street_address'))
		{
			$html .= '<address><span class="street-address">' .
				trim(nl2br($item->address)) .
				'</span><br>';
		}

		if ($item->suburb && $params->get('dr_show_suburb'))
		{
			$html .= '<span class="locality">' .
				$item->suburb .
				'</span>';
		}

		if ($item->state && $params->get('dr_show_state'))
		{
			$html .= ' <span class="region">, ' .
				$item->state .
				'</span>';
		}

		if ($item->postcode && $params->get('dr_show_postcode'))
		{
			$html .= '<span class="postal-code"> ' .
				$item->postcode .
				'</span>';
		}

		if ($item->country && $params->get('dr_show_country')
			&& ($item->address || $item->suburb || $item->state || $item->postcode))
		{
			$html .= '<span class="country-name"> ' .
				$item->country .
				'</span>';
		}

		if ($params->get('address_check') > 0
			&& ($item->address || $item->suburb || $item->state || $item->postcode))
		{
			$html .= '</address></div>';
		}

		return $html;
	}

	/**
	 * Render Address
	 *
	 * @param   object    $item    Items to render
	 * @param   Registry  $params  Params
	 * @param   object    $name    Name of Record.
	 *
	 * @return string
	 *
	 * @since 1.7.5
	 */
	public function renderPhonesNumbers($item, $params, $name = null)
	{
		$html = '';

		if ($name)
		{
			$name = $name->firstname . ' : ';
		}

		if (($params->get('other_check') > 0)
			&& ($item->email_to || $item->telephone || $item->fax || $item->mobile || $item->webpage || $item->spouse || $item->children))
		{
			$html .= '<div class="cd-info">';
		}

		if ($item->email_to && $params->get('dr_show_email'))
		{
			$html .= $name . '<span class="' . $params->get('marker_class') . '">' . $params->get('marker_email') . '&nbsp;&nbsp;' .
				$item->email_to . '</span>';
		}

		if ($item->telephone && $params->get('dr_show_telephone'))
		{
			$html .= '<br/>' . $name . '<span class="' . $params->get('marker_class') . '">' . $params->get('marker_telephone') . '&nbsp;&nbsp;' .
				nl2br($item->telephone) . '</span>';
		}

		if ($item->fax && $params->get('dr_show_fax'))
		{
			$html .= '<br/>' . $name . '<span class="' . $params->get('marker_class') . '">' .
				$params->get('marker_fax') . '&nbsp;&nbsp;' . nl2br($item->fax) . '</span>';
		}

		if ($item->mobile && $params->get('dr_show_mobile'))
		{
			$html .= '<br/>' . $name . '<span class="' . $params->get('marker_class') . '">' .
				$params->get('marker_mobile') . '&nbsp;&nbsp;' . nl2br($item->mobile) . '</span>';
		}

		if ($item->webpage && $params->get('dr_show_webpage'))
		{
			$html .= '<br/>' . $name . '<span class="' . $params->get('marker_class') . '">Site:&nbsp;&nbsp;<a href="' . $item->webpage .
				'" target="_blank">' . JText::_('COM_CHURCHDIRECTORY_WEBPAGE') . '</a></span>';
		}

		if ($params->get('other_check') > 0
			&& ($item->email_to || $item->telephone || $item->fax || $item->mobile || $item->webpage || $item->spouse || $item->children))
		{
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Get Search Field
	 *
	 * @param   \Joomla\Registry\Registry  $params  Joomla Page view Params
	 *
	 * @return string
	 *
	 * @since 1.7.8
	 * @throws \Exception
	 */
	public function getSearchField($params)
	{
		$route = 'index.php?option=com_churchdirectory&view=directory&layout=search';
		$params->def('field_size', 20);
		$suffix = $params->get('moduleclass_sfx');
		$output = '<input type="text" name="filter[search]" id="filter_search" size="'
			. $params->get('field_size', 20) . '"'
			. ' placeholder="' . JText::_('Search') . '" data-original-title=""/>';

		$showLabel  = $params->get('show_label', 1);
		$labelClass = (!$showLabel ? 'element-invisible ' : '') . 'finder' . $suffix;
		$label      = '<label for="filter_search" class="' . $labelClass . '">' . $params->get('alt_label',
				JText::_('JSEARCH_FILTER_SUBMIT')
			) . '</label>';

		switch ($params->get('label_pos', 'left'))
		{
			case 'top' :
				$output = $label . '<br />' . $output;
				break;

			case 'bottom' :
				$output .= '<br />' . $label;
				break;

			case 'right' :
				$output .= $label;
				break;

			case 'left' :
			default :
				$output = $label . " " . $output;
				break;
		}

		if ($params->get('show_button'))
		{
			$button = '<button class="btn btn-primary hasTooltip ' . $suffix . ' finder' . $suffix .
				'" type="submit" title="' . JText::_('COM_CHURCHDIRECTORY_FILTER_SUBMIT') .
				'"><span class="icon-search icon-white"></span>' . JText::_('JSEARCH_FILTER_SUBMIT') . '</button>';

			switch ($params->get('button_pos', 'left'))
			{
				case 'top' :
					$output = $button . '<br />' . $output;
					break;

				case 'bottom' :
					$output .= '<br />' . $button;
					break;

				case 'right' :
					$output .= $button;
					break;

				case 'left' :
				default :
					$output = $button . $output;
					break;
			}
		}

		$render = '<form id="com_churchdirectroy_search" action="' . JRoute::_($route) . '"
		      method="get" class="form-search">
			<div class="search' . $suffix . '">';
		$render .= $output;
		$render .= '<input type="hidden" name="option" value="com_churchdirectory">';
		$render .= '<input type="hidden" name="view" value="directory">';
		$render .= '<input type="hidden" name="layout" value="search">';
		$render .= '<input type="hidden" name="Itemid" value="' . JFactory::getApplication()->input->get('Itemid',	'0',
	'int') . '">';
		$render .= '</div>';
		$render .= '</form>';

	return $render;
	}

	/**
	 * Random Password Generator
	 *
	 * @param   int  $length  Lenght of password
	 *
	 * @return bool|string
	 *
	 * @since 1
	 */
	public function random_password($length = 8)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
		$password = substr(str_shuffle($chars), 0, $length);

		return $password;
	}
}
