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
	 * @param   int  $con_position  ID of Position
	 *
	 * @return object
	 */
	public function getPosition($con_position)
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
	 * @param   object  $params  Parameters
	 * @param   int     $id      ID of Primary Record
	 * @param   int     $famid   Family Unit ID
	 *
	 * @return string
	 */
	public function getFamilyMembersPage($params, $id, $famid)
	{

		$teacher = "\n" . '<div id="landing_table" width="100%">';
		$db      = JFactory::getDBO();
		$query   = $db->getQuery(true);

		$query->select('members.*');
		$query->from('#__churchdirectory_details AS members');
		$query->where('members.funitid = ' . (int) $famid);
		$query->order('members.name DESC');

		$db->setQuery($query->__toString());

		if ($params->get('dr_show_debug'))
		{
			var_dump($id);
			var_dump($famid);
		}
		$tresult = $db->loadObjectList();
		$t       = 0;
		$i       = 0;

		foreach ($tresult as $b)
		{
			$attribs = json_decode($b->attribs);
			$b->slug = $b->alias ? ($b->id . ':' . $b->alias) : $b->id;
			$teacher .= '<div class="directory-familymembers-list">';
			$teacher .= '<div class="directory-name"><a href="' . JRoute::_(ChurchDirectoryHelperRoute::getMemberRoute($b->slug, $b->catid)) . '">';
			$teacher .= $b->name;
			$teacher .= '</a></div>';
			$teacher .= '<div class="directory-subtitle">';

			switch ($attribs->familypostion)
			{
				case -1:
					$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_SINGLE') . '</span>';
					break;
				case 0:
					$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_HEAD_OF_HOUSEHOLD') . '</span>';
					break;
				case 1:
					$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_SPOUSE') . '</span>';
					break;
				case 2:
					$teacher .= '<span class="title">' . JText::_('COM_CHURCHDIRECTORY_CHILED') . '</span>';
					break;
			}
			$teacher .= '</div>';
			$teacher .= '<div class="clearfix"></div><div class="directory-submemberinfo">';

			if (!empty($b->con_position) && $params->get('dr_show_position'))
			{
				$teacher .= '<div class="clearfix"></div>';
				$teacher .= '<dl class="contact-position dl-horizontal">';
				$teacher .= '<dt>';

				if ($b->con_position != '-1')
				{
					$teacher .= JText::_('COM_CHURCHDIRECTORY_POSITION') . ':';
				}
				$teacher .= '</dt>';
				$teacher .= '<dd>';
				$teacher .= self::getPosition($b->con_position);
				$teacher .= '</dd></dl>';
			}

			if ($b->telephone && $params->get('dr_show_telephone'))
			{
				$teacher .= '<div class="directory-telephone"><span class="title">' . JText::_('COM_CHURCHDIRECTORY_HOME') . ':</span> ' . $b->telephone . '</div>';
			}
			if ($b->mobile && $params->get('dr_show_mobile'))
			{
				$teacher .= '<div class="directory-mobile"><span class="title">' . JText::_('COM_CHURCHDIRECTORY_MOBILE') . ':</span> ' . $b->mobile . '</div>';
			}
			$teacher .= '</div>';
			$i++;
			$t++;
			$teacher .= '</div><div class="clearfix"></div>';

			$teacher .= '<hr />';

			$this->children = $b->children;
		}
		$teacher .= '</div>';

		if ($this->children && $params->get('dr_show_children'))
		{
			$teacher .= '<div class="directory-children"><br /><span class="title">' . JText::_('COM_CHURCHDIRECTORY_CHILDREN') . ':</span> ' . $this->children . '</div>';
		}

		return $teacher;
	}

	/**
	 * Calculate rows into span's
	 *
	 * @param   int  $items_per_row  Number of Rows we want to see.
	 *
	 * @return int
	 */
	public function rowWidth($items_per_row)
	{
		$results = 12;

		if ($items_per_row == 2)
		{
			/* span6 */
			$results = 6;
		}
		elseif ($items_per_row == 3)
		{
			/* span4 */
			$results = 4;
		}
		elseif ($items_per_row == 4)
		{
			/* span2 */
			$results = 2;
		}

		return $results;
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
}
