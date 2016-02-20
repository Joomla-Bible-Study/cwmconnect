<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.model');
use Joomla\Registry\Registry;

/**
 * Methods to display a control panel.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryModelReports extends JModelLegacy
{

	/**
	 * Constructor
	 *
	 * @param   array  $config  An array of configuration options (name, state, dbo, table_path, ignore_request).
	 *
	 * @since   12.2
	 * @throws  Exception
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'name', 'a.name',
				'lname', 'a.lname',
				'suburb', 'a.suburb',
				'state', 'a.state',
				'country', 'a.country',
				'ordering', 'a.ordering',
				'sortname1', 'a.sortname1',
				'sortname2', 'a.sortname2',
				'sortname3', 'a.sortname3'
			);
		}
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   ?
	 * @param   string  $direction  ?
	 *
	 * @return void
	 *
	 * @since    1.6
	 */

	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app    = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_churchdirectory');

		// List state information
		$format = $app->input->getWord('format', '');

		if ($format == 'feed')
		{
			$limit = $app->get('feed_limit');
		}
		else
		{
			$limit = 0;
		}
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $limitstart);

		// Get list ordering default from the parameters
		$menuParams = new Registry;

		if ($menu = $app->getMenu()->getActive())
		{
			$menuParams->loadString($menu->params);
		}
		$mergedParams = clone $params;
		$mergedParams->merge($menuParams);
		$orderCol = $app->input->get('filter_order', $mergedParams->get('dinitial_sort', 'ordering'));

		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->input->get('filter_order_Dir', 'ASC');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$id = $app->input->get('id', 0, 'int');
		$this->setState('category.id', $id);
		$user = JFactory::getUser();

		if ((!$user->authorise('core.edit.state', 'com_churchdirectory')) && (!$user->authorise('core.edit', 'com_churchdirectory')))
		{
			// Limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);

			// Filter by start and end dates.
			$this->setState('filter.publish_date', true);
		}
		$mstatus = $app->input->get('filter_mstatus', $mergedParams->get('mstatus', '0'));
		$this->setState('filter.mstatus', $mstatus);

		// Load the parameters.
		$this->setState('params', $params);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return JDatabaseQuery
	 * @since 1.6
	 */
	protected function getListQuery()
	{

		$user = JFactory::getUser();

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// SQL sqlsrv changes
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

		$query->select($this->getState('item.select', 'a.*') . ',' . $case_when . ',' . $case_when1)
			->from('#__churchdirectory_details AS a');

		// Join on KML table.
		$query->select('k.name AS kml_name, k.style AS kml_style, k.params AS kml_params,
		 k.alias AS kml_alias, k.access AS kml_access, k.lat AS kml_lat, k.lng AS kml_lng');
		$query->join('LEFT', '#__churchdirectory_kml AS k on k.id = a.kmlid');

		// Join on Family Unit.
		$query->select('fu.id AS funit_id, fu.name AS funit_name, fu.image as funit_image, fu.access as funit_access');
		$query->join('LEFT', '#__churchdirectory_familyunit AS fu ON fu.id = a.funitid');

		// Join over the categories.
		$query->select('c.title AS category_title, c.params AS category_params, c.alias AS category_alias, c.access AS category_access');
		$query->join('INNER', '#__categories AS c ON c.id = a.catid');

		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author")
			->select("ua.email AS author_email")
			->join('LEFT', '#__users AS ua ON ua.id = a.created_by')
			->join('LEFT', '#__users AS uam ON uam.id = a.modified_by');

		// Join over the categories to get parent category titles
		$query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
			->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

		// Join to check for category published state in parent categories up the tree
		$query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = "SELECT 'cat.id' AS id FROM `#__categories` AS cat JOIN `#__categories` AS parent ";
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');

		if ($this->getState('filter.published') == 2)
		{
			// Find any up-path categories that are archived
			// If any up-path categories are archived, include all children in archived layout
			$subquery .= ' AND parent.published = 2 GROUP BY cat.id ';

			// Set effective state to archived if up-path category is archived
			$publishedWhere = 'CASE WHEN badcats.id is null THEN a.state ELSE 2 END';
		}
		else
		{
			// Find any up-path categories that are not published
			// If all categories are published, badcats.id will be null, and we just use the article state
			$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';

			// Select state to unpublished if up-path category is unpublished
			$publishedWhere = 'CASE WHEN badcats.id is null THEN a.state ELSE 0 END';
		}

		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')')
				->where('c.access IN (' . $groups . ')');
		}

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			// Use article state if badcats.id is null, otherwise, force 0 for unpublished
			$query->where($publishedWhere . ' = ' . (int) $published);
		}
		elseif (is_array($published))
		{
			Joomla\Utilities\ArrayHelper::toInteger($published);
			$published = implode(',', $published);

			// Use article state if badcats.id is null, otherwise, force 0 for unpublished
			$query->where($publishedWhere . ' IN (' . $published . ')');
		}

		// Define null and now dates
		$nullDate = $db->quote($db->getNullDate());
		$nowDate  = $db->quote(JFactory::getDate()->toSql());

		// Filter by start and end dates.
		if ((!$user->authorise('core.edit.state', 'com_churchdirectory')) && (!$user->authorise('core.edit', 'com_churchdirectory')))
		{
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
				->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		}

		// Filter by Date Range or Relative Date
		$dateFiltering = $this->getState('filter.date_filtering', 'off');
		$dateField     = $this->getState('filter.date_field', 'a.created');

		switch ($dateFiltering)
		{
			case 'range':
				$startDateRange = $db->quote($this->getState('filter.start_date_range', $nullDate));
				$endDateRange   = $db->quote($this->getState('filter.end_date_range', $nullDate));
				$query->where(
					'(' . $dateField . ' >= ' . $startDateRange . ' AND ' . $dateField .
					' <= ' . $endDateRange . ')'
				);
				break;

			case 'relative':
				$relativeDate = (int) $this->getState('filter.relative_date', 0);
				$query->where(
					$dateField . ' >= DATE_SUB(' . $nowDate . ', INTERVAL ' .
					$relativeDate . ' DAY)'
				);
				break;

			case 'off':
			default:
				break;
		}

		// Filter by Member Status
		$query->where('a.mstatus = ' . $this->getState('filter.mstatus'));

		// Filter by language
		if ($this->getState('filter.language'))
		{
			$query->where('a.language in (' . $db->Quote(JFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
		}

		// Set sortname ordering if selected
		$query->order($db->escape('a.id') . ' ' . $db->escape('ASC'));

		return $query;
	}

	/**
	 * CVS Dump
	 *
	 * @return bool
	 */
	public function getCsv()
	{
		$this->populateState();
		$db    = $this->getDbo();
		$items = $db->setQuery($this->getListQuery())->loadObjectList();
		$csv   = fopen('php://output', 'w');
		$cols = array();
		foreach ($items as $cal => $line)
		{
			if ($cal == 0)
			{
				foreach ($line as $c => $item)
				{
					if ($c == 'params')
					{
						$reg = new Joomla\Registry\Registry;
						$reg->loadString($item);
						$params = $reg->toArray();
						foreach ($params as $p => $itemp)
						{
							$cols[] = $p;
						}
					}
					elseif ($c == 'attribs')
					{
						$reg = new Joomla\Registry\Registry;
						$reg->loadString($item);
						$params = $reg->toArray();
						foreach ($params as $p => $itemp)
						{
							$cols[] = $p;
						}
					}
					elseif ($c == 'kml_params')
					{
						$reg = new Joomla\Registry\Registry;
						$reg->loadString($item);
						$params = $reg->toArray();
						foreach ($params as $p => $itemp)
						{
							$cols[] = $p;
						}
					}
					elseif ($c == 'category_params')
					{
						$reg = new Joomla\Registry\Registry;
						$reg->loadString($item);
						$params = $reg->toArray();
						foreach ($params as $p => $itemp)
						{
							$cols[] = $p;
						}
					}
					else
					{
						$cols[] = $c;
					}
				}
			}
		}
		fputcsv($csv, $cols);

		$lines = new stdClass;
		$linet = array();
		foreach ($items as $line)
		{
			foreach ($line as $c => $item)
			{
				if ($c == 'params')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toArray();
					foreach ($params as $p => $itemp)
					{
						$lines->$c = $itemp;
					}
				}
				elseif ($c == 'attribs')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toArray();
					foreach ($params as $p => $itemp)
					{
						if ($p == 'sex')
						{
							switch ($itemp)
							{
								case (0):
									$lines->$p = 'M';
									break;
								case (1):
									$lines->$p = 'F';
									break;
							}
						}
						else
						{
							$lines->$p = $itemp;
						}
					}
				}
				elseif ($c == 'kml_params')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toArray();
					foreach ($params as $p => $itemp)
					{
						$lines->$c = $itemp;
					}
				}
				elseif ($c == 'category_params')
				{
					$reg = new Joomla\Registry\Registry;
					$reg->loadString($item);
					$params = $reg->toArray();
					foreach ($params as $p => $itemp)
					{
						$lines->$c = $itemp;
					}
				}
				else
				{
					$lines->$c = $item;
				}
			}
			foreach ($line as $s => $item)
			{
				if (isset($lines->$s))
				{
					$linet[] = $lines->$s;
				}
				else
				{
					$linet[] = '';
				}
			}
			fputcsv($csv, (array) $lines);
			$lines = new stdClass;
			$linet = array();

		}

		return fclose($csv);
	}

}
