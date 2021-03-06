<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Class list category
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryModelCategory extends JModelList
{
	/**
	 * Category items data
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $item = null;

	/**
	 * Protect _articles
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $articles = null;

	/**
	 * Protect _siblings
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $siblings = null;

	/**
	 * Protect _children
	 *
	 * @access protected
	 * @var array
	 * @since       1.7.2
	 */
	protected $children = null;

	/**
	 * Protect _parent
	 *
	 * @access protected
	 * @var object
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

	protected $rightsibling;

	protected $leftsibling;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since       1.7.2
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'a.id',
				'name', 'a.name',
				'con_position', 'a.con_position',
				'suburb', 'a.suburb',
				'state', 'a.state',
				'country', 'a.country',
				'ordering', 'a.ordering',
				'sortname',
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

		// Convert the params field into an object, saving original in params
		foreach ($items as $i => $iValue)
		{
			$item = & $items[$i];

			if (!isset($this->params))
			{
				$item->params = new Registry($item->params);
			}

			$this->tags = new JHelperTags;
			$this->tags->getItemTags('com_churchdirectory.member', $item->id);
		}

		return $items;
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return   string    An SQL query
	 *
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
		// -- sqlsrv changes
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
		$query->select($this->getState('list.select', 'a.*') . ',' . $case_when . ',' . $case_when1);
		$query->from('`#__churchdirectory_details` AS a');
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');
		$query->where('a.access IN (' . $groups . ')');

		// Filter by category.
		if ($categoryId = $this->getState('category.id'))
		{
			$query->where('a.catid = ' . (int) $categoryId);
			$query->where('c.access IN (' . $groups . ')');
		}

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
		$nullDate = $db->q($db->getNullDate());
		$nowDate  = $db->q(JFactory::getDate()->toSql());

		if ($this->getState('filter.publish_date'))
		{
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
			$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
		}

		// Filter by search in title
		$search = $this->getState('list.filter');

		if (!empty($search))
		{
			$search = $db->q('%' . $db->escape($search, true) . '%');
			$query->where('(a.name LIKE ' . $search . ')');
		}

		// Join over the categories.
		$query->select('ca.title AS category_title, ca.params AS category_params, ca.description AS category_description,' .
			' ca.alias AS category_alias, ca.access AS category_access');
		$query->join('INNER', '#__categories AS ca ON ca.id = a.catid');

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

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 * @throws \Exception
	 * @since   1.6
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app    = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_churchdirectory');

		// List state information
		$format = $app->input->getWord('format');

		if ($format === 'feed')
		{
			$limit = $app->get('feed_limit');
		}
		else
		{
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'));
		}

		if (!$app->input->getInt('print'))
		{
			$this->setState('list.limit', $limit);

			$limitstart = $app->input->get('limitstart', 0, '', 'int');
			$this->setState('list.start', $limitstart);
		}

		// Optional filter text
		$this->setState('list.filter', $app->input->getString('filter-search', ''));

		// Get list ordering default from the parameters
		$menuParams = new Registry;

		if ($menu = $app->getMenu()->getActive())
		{
			$menuParams->loadString($menu->params);
		}

		$mergedParams = clone $params;
		$mergedParams->merge($menuParams);
		$orderCol = $app->input->get('filter_order', $mergedParams->get('initial_sort', 'ordering'));

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

		$this->setState('filter.language', $app->getLanguageFilter());

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
			$options['countItems'] = $params->get('show_cat_items', 1) || $params->get('show_empty_categories', 0);
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
	 * Get right Sibling
	 *
	 * @return string
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
	 * Increment the hit counter for the category.
	 *
	 * @param   integer  $pk  Optional primary key of the category to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 *
	 * @since   3.2
	 */
	public function hit($pk = 0)
	{
		$input = JFactory::getApplication()->input;
		$hitcount = $input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

			$table = JTable::getInstance('Category', 'JTable');
			$table->load($pk);
			$table->hit($pk);
		}

		return true;
	}
}
