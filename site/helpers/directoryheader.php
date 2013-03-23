<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.JoomlaBibleStudy.org
 * */

defined('_JEXEC') or die;

/**
 * Directory Header Helper
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.1
 */
class DirectoryHeaderHelper
{

	/**
	 * Get Header html
	 *
	 * @param   JRegistry  $params  HTML Params
	 *
	 * @return string
	 */
	public static function getHeader($params)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('a.*');
		$query->from('#__churchdirectory_dirheader AS a');
		$query->order('a.ordering ASC');
		$db->setQuery($query);

		$result = $db->loadObjectList();
		$h      = 0;
		$header = null;

		foreach ($result as $b)
		{
			$header .= '<div class="headerpage">';

			if ($params->get('dr_show_debug'))
			{
				$header .= '<p>ID: ' . $b->id . '</p>';
				$header .= '<p>Count: ' . $h . '</p>';
			}
			$header .= $b->description;
			$header .= '</div>';
			$header .= '<div style="page-break-after:always"></div>';
			$h++;
		}

		return $header;
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
