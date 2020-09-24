<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2011 NFSDA. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Class list for Directory
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryModelDirectory extends JModelList
{
	/**
	 * Set view_item
	 *
	 * @access   protected
	 * @since    1.6
	 */
	protected $view_item = 'directory';

	/**
	 * Category items data
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $item = null;

	/**
	 * Articles
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $articles = null;

	/**
	 * Siblings
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $siblings = null;

	/**
	 * Childern items
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $children = null;

	/**
	 * Perent
	 *
	 * @access protected
	 * @var string
	 * @since       1.7.2
	 */
	protected $parent = null;

	/**
	 * The category that applies.
	 *
	 * @access    protected
	 * @var        object
	 * @since       1.7.2
	 */
	protected $category = null;

	/**
	 * The list of other newfeed categories.
	 *
	 * @access    protected
	 * @var        array
	 * @since       1.7.2
	 */
	protected $categories = null;

	protected $kparams;

	protected $cparams;

	protected $params;

	protected $attribs;

	protected $leftsibling;

	protected $rightsibling;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
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
			];
		}

		parent::__construct($config);
	}

	/**
	 * Method to get a list of items.
	 *
	 * @return    mixed    An array of objects on success, false on failure.
	 *
	 * @since       1.7.2
	 */
	public function getItems()
	{
		// Invoke the parent getItems method to get the main list
		$items = parent::getItems();

		// Convert the params field into an object, saving original in _params
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = & $items[$i];

			if (!isset($this->kparams))
			{
				$kparams = new Registry;
				$kparams->loadString($item->kml_params);
				$item->kml_params = $kparams;
			}

			if (!isset($this->cparams))
			{
				$cparams = new Registry;
				$cparams->loadString($item->category_params);
				$item->category_params = $cparams;
			}

			if (!isset($this->params))
			{
				$params = new Registry;
				$params->loadString($item->params);
				$item->params = $params;
			}

			if (!isset($this->attribs))
			{
				$params = new Registry;
				$params->loadString($item->attribs);
				$item->attribs = $params;
			}
		}

		return $items;
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return  string  An SQL query
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$user   = JFactory::getUser();

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// SQL sqlsrv changes
		$case_when = ' CASE WHEN ';
		$case_when .= $query->charLength('a.alias', '!=', '0');
		$case_when .= ' THEN ';
		$a_id = $query->castAsChar('a.id');
		$case_when .= $query->concatenate([$a_id, 'a.alias'], ':');
		$case_when .= ' ELSE ';
		$case_when .= $a_id . ' END as slug';

		$case_when1 = ' CASE WHEN ';
		$case_when1 .= $query->charLength('c.alias', '!=', '0');
		$case_when1 .= ' THEN ';
		$c_id = $query->castAsChar('c.id');
		$case_when1 .= $query->concatenate([$c_id, 'c.alias'], ':');
		$case_when1 .= ' ELSE ';
		$case_when1 .= $c_id . ' END as catslug';

		$query->select($this->getState('item.select', 'a.*') . ',' . $case_when . ',' . $case_when1)
			->from('#__churchdirectory_details AS a');

		// Join on KML table.
		$query->select('k.name AS kml_name, k.style AS kml_style, k.params AS kml_params, k.alias AS kml_alias,
		k.access AS kml_access, k.lat AS kml_lat, k.lng AS kml_lng');
		$query->join('LEFT', '#__churchdirectory_kml AS k on k.id = a.kmlid');

		// Join on Family Unit.
		$query->select('fu.id AS funit_id, fu.name AS funit_name, fu.image as funit_image, fu.access as funit_access');
		$query->join('LEFT', '#__churchdirectory_familyunit AS fu ON fu.id = a.funitid');

		// Join over the categories.
		$query->select('c.title AS category_title, c.params AS category_params, c.alias AS category_alias,' .
			' c.description AS category_description, c.access AS category_access');
		$query->join('INNER', '#__categories AS c ON c.id = a.catid');

		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author")
			->select("ua.email AS author_email")

			->join('LEFT', '#__users AS ua ON ua.id = a.created_by')
			->join('LEFT', '#__users AS uam ON uam.id = a.modified_by');

		// Join over the categories to get parent category titles
		$query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route,' .
			' parent.alias as parent_alias')
			->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

		// Join to check for category published state in parent categories up the tree
		$query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
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

		$query->where('a.published = 1');

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
			$query->where('a.language in (' . $db->q(JFactory::getLanguage()->getTag()) . ',' . $db->q('*') . ')');
		}

		// Set sortname ordering if selected
		if ($this->getState('list.ordering') === 'sortname')
		{
			$query->order($db->escape('a.sortname1') . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
			$query->order($db->escape('a.sortname2') . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
			$query->order($db->escape('a.sortname3') . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
		}
		else
		{
			$query->order($db->escape($this->getState('list.ordering', 'a.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
		}

		if ($search = $this->getState('filter.search'))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'suburb:') === 0)
			{
				$search = $db->q('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 7)), true) . '%'));
				$query->where($db->qn('a.suburb') . ' LIKE ' . $search);
			}
			elseif (stripos($search, 'address:') === 0)
			{
				$search = $db->q('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 8)), true) . '%'));
				$query->where($db->qn('a.address') . ' LIKE ' . $search);
			}
			elseif (stripos($search, 'zip:') === 0)
			{
				$search = $db->q('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 4)), true) . '%'));
				$query->where('a.postcode LIKE ' . $search);
			}
			else
			{
				$search = $db->q('%' . $db->escape($search, true) . '%');
				$query->where('(' . $db->qn('a.name') . ' LIKE ' . $search . ' OR ' . $db->qn('a.alias') . ' LIKE ' . $search . ')');
			}
		}

		return $query;
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

		if ($format === 'feed')
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

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'ordering';
		}

		$this->setState('list.ordering', $orderCol);

		$listOrder = $app->input->get('filter_order_Dir', 'ASC');

		if (!in_array(strtoupper($listOrder), ['ASC', 'DESC', '']))
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
		$this->setState('filter.language', $app->getLanguageFilter());

		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));

		// Load the parameters.
		$this->setState('params', $params);
	}

	/**
	 * Method to get category data for the current category
	 *
	 * @return    object
	 *
	 * @since    1.5
	 */
	public function getCategory()
	{
		if (!is_object($this->item))
		{
			$app    = JFactory::getApplication();
			$menu   = $app->getMenu();
			$active = $menu->getActive();
			$params = new Registry;

			if ($active)
			{
				$params->loadString($active->params);
			}

			$options               = [];
			$options['countItems'] = $params->get('db_show_cat_items', 1) || $params->get('db_show_empty_categories', 0);
			$categories            = JCategories::getInstance('ChurchDirectory', $options);
			$this->item            = $categories->get($this->getState('category.id', 'root'));

			if (is_object($this->item))
			{
				$this->children = $this->item->getChildren();
				$this->parent   = false;

				if ($this->item->getParent())
				{
					$this->parent = $this->item->getParent();
				}

				$this->rightsibling = $this->item->getSibling();
				$this->leftsibling  = $this->item->getSibling(false);
			}
			else
			{
				$this->children = false;
				$this->parent   = false;
			}
		}

		return $this->item;
	}

	/**
	 * Get the parent category.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 *
	 * @since       1.7.2
	 */
	public function getParent()
	{
		if (!is_object($this->item))
		{
			$this->getCategory();
		}

		return $this->parent;
	}

	/**
	 * Get the sibling (adjacent) categories.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 *
	 * @since       1.7.2
	 */
	public function &getLeftSibling()
	{
		if (!is_object($this->item))
		{
			$this->getCategory();
		}

		return $this->leftsibling;
	}

	/**
	 * Get the sibling (adjacent) categories.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 *
	 * @since       1.7.2
	 */
	public function &getRightSibling()
	{
		if (!is_object($this->item))
		{
			$this->getCategory();
		}

		return $this->rightsibling;
	}

	/**
	 * Get the child categories.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 *
	 * @since       1.7.2
	 */
	public function &getChildren()
	{
		if (!is_object($this->item))
		{
			$this->getCategory();
		}

		return $this->children;
	}

	/**
	 * Get Search results
	 *
	 * @return bool|mixed
	 *
	 * @since 1.7.8
	 */
	public function getSearch()
	{
		$q = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');

		if (!$q)
		{
			return false;
		}

		return $this->getItems();
	}
}
