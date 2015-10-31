<?php
/**
 * @package        ChurchDirectory.Site
 * @copyright      2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die;


jimport('joomla.application.component.modellist');
jimport('joomla.event.dispatcher');

/**
 * Module for Members
 *
 * @package  ChurchDirectory.Site
 * @since    2.5
 */
class ChurchDirectoryModelHome extends JModelList
{

	/**
	 * Protect view
	 *
	 * @var string
	 */
	protected $view_item = 'Home';

	/**
	 * Protect item
	 *
	 * @var int
	 */
	protected $_item = null;

	protected $member;

	/**
	 * Model context string.
	 *
	 * @var        string
	 */
	protected $_context = 'com_churchdirectory.home';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string $ordering  An optional ordering field.
	 * @param   string $direction An optional direction (asc|desc).
	 *
	 * @since    1.6
	 * @return  void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication('site');

		$return = $app->input->get('return', $this->setReturnPage(), 'base64');
		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);
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

			if (!isset($this->_params))
			{
				$params = new JRegistry;
				$params->loadString($item->params);
				$item->params = $params;
			}
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
		$query->from('`#__churchdirectory_details` AS a');
		$query->join('LEFT', '#__categories AS c ON c.id = a.catid');
		$query->where('a.access IN (' . $groups . ')');

		// Join over the users for the author and modified_by names.
		$query->select("CASE WHEN a.created_by_alias > ' ' THEN a.created_by_alias ELSE ua.name END AS author");
		$query->select("ua.email AS author_email");

		$query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');
		$query->join('LEFT', '#__users AS uam ON uam.id = a.modified_by');

		$query->where('a.published = ' . (int) 1);

		$query->where('a.featured = ' . (int) 1);
		$query->order($db->escape('a.ordering') . ' ' . $db->escape('ASC'));

		return $query;
	}

	/**
	 * Get the return URL.
	 *
	 * @return    string    The return URL.
	 *
	 * @since    1.6
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	/**
	 * Set Return Page if non passed
	 *
	 * @return string URL of current page.
	 */
	public function setReturnPage()
	{
		$Itemid = JFactory::getApplication()->input->getInt('Itemid');
		if ($Itemid)
		{
			$Itemid = '&Itemid=' . $Itemid;
		}

		return base64_encode('index.php?option=' . $this->option . '&view=' . $this->view_item . $Itemid);
	}

}
