<?php
/**
 * @package    Search.ChurchDirectory
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * ChurchDirectory Search plugin
 *
 * @package  Search.ChurchDirectory
 * @since    1.7.0
 */
class plgSearchChurchdirectory extends JPlugin
{

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 *
	 * @since       1.7.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		$this->loadLanguage('com_churchdirectory', JPATH_ADMINISTRATOR);
	}

	/**
	 * Content Search Areas
	 *
	 * @return  array An array of search areas
	 */
	public function onContentSearchAreas()
	{
		static $areas = array(
			'churchdirectory' => 'PLG_SEARCH_CHURCHDIRECTORY_MEMBERS'
		);

		return $areas;
	}

	/**
	 * ChurchDirectory Search method
	 *
	 * The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 *
	 * @param   string  $text      Target search string
	 * @param   string  $phrase    Mathcing option, exact|any|all
	 * @param   string  $ordering  Ordering option, newest|oldest|popular|alpha|category
	 * @param   string  $areas     ?
	 *
	 * @return  mixed
	 */
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$db     = JFactory::getDbo();
		$app    = JFactory::getApplication();
		$user   = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		if (is_array($areas))
		{
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas())))
			{
				return array();
			}
		}

		$sContent  = $this->params->get('search_content', 1);
		$sArchived = $this->params->get('search_archived', 1);
		$limit     = $this->params->def('search_limit', 50);
		$state     = array();
		if ($sContent)
		{
			$state[] = 1;
		}
		if ($sArchived)
		{
			$state[] = 2;
		}

		$text = trim($text);
		if ($text == '')
		{
			return array();
		}

		$section = JText::_('PLG_SEARCH_CHURCHDIRECTORY_MEMBERS');

		switch ($ordering)
		{
			case 'alpha':
				$order = 'a.name ASC';
				break;

			case 'category':
				$order = 'c.title ASC, a.name ASC';
				break;

			case 'popular':
			case 'newest':
			case 'oldest':
			default:
				$order = 'a.name DESC';
		}

		$text = $db->Quote('%' . $db->escape($text, true) . '%', false);

		$rows = array();

		if (!empty($state))
		{
			$query = $db->getQuery(true);

			// -- sqlsrv changes
			$case_when = ' CASE WHEN ';
			$case_when .= $query->charLength('a.alias');
			$case_when .= ' THEN ';
			$a_id = $query->castAsChar('a.id');
			$case_when .= $query->concatenate(array($a_id, 'a.alias'), ':');
			$case_when .= ' ELSE ';
			$case_when .= $a_id . ' END as slug';

			$case_when1 = ' CASE WHEN ';
			$case_when1 .= $query->charLength('c.alias');
			$case_when1 .= ' THEN ';
			$c_id = $query->castAsChar('c.id');
			$case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
			$case_when1 .= ' ELSE ';
			$case_when1 .= $c_id . ' END as catslug';

			$query->select('a.name AS title, \'\' AS created, a.misc, '
				. $case_when . ',' . $case_when1 . ', '
				. $query->concatenate(array("a.name", "a.misc"), ",") . ' AS text,'
				. $query->concatenate(array($db->Quote($section), "c.title"), " / ") . ' AS section,'
				. '\'2\' AS browsernav');
			$query->from('#__churchdirectory_details AS a');
			$query->innerJoin('#__categories AS c ON c.id = a.catid');
			$query->where('(a.name LIKE ' . $text . 'OR a.misc LIKE ' . $text
				. 'OR a.address LIKE ' . $text . 'OR a.suburb LIKE ' . $text . 'OR a.state LIKE ' . $text
				. 'OR a.country LIKE ' . $text . 'OR a.postcode LIKE ' . $text . 'OR a.telephone LIKE ' . $text
				. 'OR a.fax LIKE ' . $text . ') AND a.published IN (' . implode(',', $state) . ') AND c.published=1 '
				. 'AND a.access IN (' . $groups . ') AND c.access IN (' . $groups . ')');
			$query->group('a.id, a.misc');
			$query->order($order);

			// Filter by language
			if ($app->isSite() && $app->getLanguageFilter())
			{
				$tag = JFactory::getLanguage()->getTag();
				$query->where('a.language in (' . $db->Quote($tag) . ',' . $db->Quote('*') . ')');
				$query->where('c.language in (' . $db->Quote($tag) . ',' . $db->Quote('*') . ')');
			}

			$db->setQuery($query, 0, $limit);
			$rows = $db->loadObjectList();

			if ($rows)
			{
				foreach ($rows as $key => $row)
				{
					$rows[$key]->href = 'index.php?option=com_churchdirectory&view=member&id=' . $row->slug . '&catid=' . $row->catslug;
					$rows[$key]->text = $row->title;
					$rows[$key]->text .= ($row->misc) ? ', ' . $row->misc : '';
				}
			}
		}

		return $rows;
	}

}
