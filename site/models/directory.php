<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2011 NFSDA. All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

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
	 * @access protected
	 * @since    1.6
	 */
	protected $view_item = 'directory';

	/**
	 * Category items data
	 *
	 * @access protected
	 * @var array
	 */
	protected $_item = null;

	/**
	 * Articles
	 *
	 * @access protected
	 * @var array
	 */
	protected $_articles = null;

	/**
	 * Siblings
	 *
	 * @access protected
	 * @var array
	 */
	protected $_siblings = null;

	/**
	 * Childern items
	 *
	 * @access protected
	 * @var array
	 */
	protected $_children = null;

	/**
	 * Perent
	 *
	 * @access protected
	 * @var string
	 */
	protected $_parent = null;

	/**
	 * The category that applies.
	 *
	 * @access    protected
	 * @var        object
	 */
	protected $_category = null;

	/**
	 * The list of other newfeed categories.
	 *
	 * @access    protected
	 * @var        array
	 */
	protected $_categories = null;

	protected $_kparams;

	protected $_cparams;

	protected $_params;

	protected $_attribs;

	protected $_leftsibling;

	protected $_rightsibling;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
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
	 * Method to get a list of items.
	 *
	 * @return    mixed    An array of objects on success, false on failure.
	 */
	public function getItems()
	{
		// Invoke the parent getItems method to get the main list
		$items = parent::getItems();

		// Convert the params field into an object, saving original in _params
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = & $items[$i];

			if (!isset($this->_kparams))
			{
				$kparams = new JRegistry;
				$kparams->loadString($item->kml_params);
				$item->kml_params = $kparams;
			}
			if (!isset($this->_cparams))
			{
				$cparams = new JRegistry;
				$cparams->loadString($item->category_params);
				$item->category_params = $cparams;
			}
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
	 * Method to build an SQL query to load the list data.
	 *
	 * @return    string    An SQL query
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$user   = JFactory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select required fields from the categories.
		//sqlsrv changes
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
		$query->select($this->getState('list.select', 'a.*') . ',' . $case_when . ',' . $case_when1);
		$query->from($db->quoteName('#__churchdirectory_details') . ' AS a');
		$query->where('a.published=1');

		// Join on KML table.
		$query->select('k.name AS kml_name, k.style AS kml_style, k.params AS kml_params, k.alias AS kml_alias, k.access AS kml_access, k.lat AS kml_lat, k.lng AS kml_lng');
		$query->join('LEFT', '#__churchdirectory_kml AS k on k.id = a.kmlid');

		// Join on Family Unit.
		$query->select('fu.id AS funit_id, fu.name AS funit_name, fu.image as funit_image, fu.access as funit_access');
		$query->join('LEFT', '#__churchdirectory_familyunit AS fu ON fu.id = a.funitid');

		// Join on category table.
		$query->select('c.title AS category_title, c.params AS category_params, c.alias AS category_alias, c.access AS category_access');
		$query->where('a.access IN (' . $groups . ')');
		$query->join('INNER', '#__categories AS c ON c.id = a.catid');
		$query->where('c.access IN (' . $groups . ')');


		// Filter by category.
		if ($categoryId = $this->getState('category.id')) {
			$query->where('a.catid = '.(int) $categoryId);
			$query->where('c.access IN ('.$groups.')');
		}

		// Join to check for category published state in parent categories up the tree
		// TODO need to redo the Query;
		$query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->quote('com_churchdirectory');
		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the churchdirectory state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';
		// Select state to unpublished if up-path category is unpublished
		$publishedWhere = 'CASE WHEN badcats.id is null THEN a.published ELSE 0 END';
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author");
		$query->select("ua.email AS author_email");

		$query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');
		$query->join('LEFT', '#__users AS uam ON uam.id = a.modified_by');

		// Filter by state
		$state = $this->getState('filter.published');
		if (is_numeric($state))
		{
			$query->where('a.published = ' . (int) $state);
		}
		// Filter by start and end dates.
		$nullDate = $db->Quote($db->getNullDate());
		$nowDate  = $db->Quote(JFactory::getDate()->toSql());

		if ($this->getState('filter.publish_date'))
		{
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
			$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		}

		// Filter by language
		if ($this->getState('filter.language'))
		{
			$query->where('a.language in (' . $db->Quote(JFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
		}

		// Set sortname ordering if selected
		if ($this->getState('list.ordering') == 'sortname')
		{
			$query->order($db->escape('a.sortname1') . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
			$query->order($db->escape('a.sortname2') . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
			$query->order($db->escape('a.sortname3') . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
		}
		else
		{
			$query->order($db->escape($this->getState('list.ordering', 'a.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));
		}

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param string $ordering
	 * @param string $direction
	 *
	 * @since    1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app    = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_churchdirectory');
		$db     = $this->getDbo();

		// List state information
		$format = $app->input->getWord('format');
		if ($format == 'feed')
		{
			$limit = $app->getCfg('feed_limit');
		}
		else
		{
			$limit = 0;
		}
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $limitstart);

		// Get list ordering default from the parameters
		$menuParams = new JRegistry;
		if ($menu = $app->getMenu()->getActive()) {
			$menuParams->loadString($menu->params);
		}
		$mergedParams = clone $params;
		$mergedParams->merge($menuParams);

		$orderCol	= $app->input->get('filter_order', $mergedParams->get('dinitial_sort', 'ordering'));
		if (!in_array($orderCol, $this->filter_fields)) {
			$orderCol = 'ordering';
		}
		$this->setState('list.ordering', $orderCol);

		$listOrder	= $app->input->get('filter_order_Dir', 'ASC');
		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
			$listOrder = 'ASC';
		}
		$this->setState('list.direction', $listOrder);

		$id = $app->input->get('id', 0, 'int');
		$this->setState('category.id', $id);

		$user = JFactory::getUser();
		if ((!$user->authorise('core.edit.state', 'com_churchdirectory')) && (!$user->authorise('core.edit', 'com_churchdirectory')))
		{
			// limit to published for people who can't edit or edit.state.
			$this->setState('filter.published', 1);

			// Filter by start and end dates.
			$this->setState('filter.publish_date', true);
		}
		$this->setState('filter.language', $app->getLanguageFilter());

		// Load the parameters.
		$this->setState('params', $params);
	}

	/**
	 * Method to get category data for the current category
	 *
	 * @return    object
	 * @since    1.5
	 */
	public function getCategory()
	{
		if (!is_object($this->_item))
		{
			$app    = JFactory::getApplication();
			$menu   = $app->getMenu();
			$active = $menu->getActive();
			$params = new JRegistry();

			if ($active)
			{
				$params->loadString($active->params);
			}

			$options               = array();
			$options['countItems'] = $params->get('db_show_cat_items', 1) || $params->get('db_show_empty_categories', 0);
			$categories            = JCategories::getInstance('ChurchDirectory', $options);
			$this->_item           = $categories->get($this->getState('category.id', 'root'));
			if (is_object($this->_item))
			{
				$this->_children = $this->_item->getChildren();
				$this->_parent   = false;
				if ($this->_item->getParent())
				{
					$this->_parent = $this->_item->getParent();
				}
				$this->_rightsibling = $this->_item->getSibling();
				$this->_leftsibling  = $this->_item->getSibling(false);
			}
			else
			{
				$this->_children = false;
				$this->_parent   = false;
			}
		}

		return $this->_item;
	}

	/**
	 * Get the parent category.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 */
	public function getParent()
	{
		if (!is_object($this->_item))
		{
			$this->getCategory();
		}

		return $this->_parent;
	}

	/**
	 * Get the sibling (adjacent) categories.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 */
	function &getLeftSibling()
	{
		if (!is_object($this->_item))
		{
			$this->getCategory();
		}

		return $this->_leftsibling;
	}

	/**
	 * Get the sibling (adjacent) categories.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 */
	function &getRightSibling()
	{
		if (!is_object($this->_item))
		{
			$this->getCategory();
		}

		return $this->_rightsibling;
	}

	/**
	 * Get the child categories.
	 *
	 * @return    mixed    An array of categories or false if an error occurs.
	 */
	function &getChildren()
	{
		if (!is_object($this->_item))
		{
			$this->getCategory();
		}

		return $this->_children;
	}

}
