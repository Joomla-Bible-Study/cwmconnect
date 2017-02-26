<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Methods to display a control panel.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryModelReports extends JModelAdmin
{
	/**
	 * The type alias for this content type.
	 *
	 * @var    string
	 * @since  3.2
	 */
	public $typeAlias = 'com_churchdirectory.reports';

	/**
	 * The context used for the associations table
	 *
	 * @var    string
	 * @since  3.4.4
	 */
	protected $associationsContext = 'com_churchdirectory.item';

	/**
	 * Batch copy/move command. If set to false, the batch copy/move command is not supported
	 *
	 * @var  string
	 * @since    1.7.0
	 */
	protected $batch_copymove = 'category_id';

	/**
	 * Method to get the row form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    mixed    A JForm object on success, false on failure
	 *
	 * @since    1.7.0
	 */
	public function getForm($data = [], $loadData = true)
	{
		JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_users/models/fields');

		// Get the form.
		$form = $this->loadForm('com_churchdirectory.reports', 'reports', ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		// Modify the form based on access controls.
		if (!$this->canEditState((object) $data))
		{
			// Disable fields for display.
			$form->setFieldAttribute('featured', 'disabled', 'true');
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is a record you can edit.
			$form->setFieldAttribute('featured', 'filter', 'unset');
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

		return $form;
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

		$order = $app->input->get('filter_order', $mergedParams->get('order', 'a.id'));
		$this->setState('filter.order', $order);

		// Load the parameters.
		$this->setState('params', $params);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return JDatabaseQuery
	 *
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
			->select("ua.email AS author_email, ua.name AS created_by, ua.name AS modified_by, user.name AS user_id")
			->join('LEFT', '#__users AS ua ON ua.id = a.created_by')
			->join('LEFT', '#__users AS uam ON uam.id = a.modified_by')
			->join('LEFT', '#__users AS user ON user.id = a.user_id');

		// Join over the categories to get parent category titles
		$query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
			->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

		// Change for sqlsrv... aliased c.published to cat_published
		// Join to check for category published state in parent categories up the tree
		$query->select('c.published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id AS id FROM `#__categories` AS cat JOIN `#__categories` AS parent ';
		$query->select('c.published as cat_published, CASE WHEN badcats.id is null THEN c.published ELSE 0 END AS parents_published');
		$subquery = 'SELECT cat.id AS id FROM `#__categories` AS cat JOIN `#__categories` AS parent ';
		$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
		$subquery .= 'WHERE parent.extension = ' . $db->q('com_churchdirectory');

		// Find any up-path categories that are not published
		// If all categories are published, badcats.id will be null, and we just use the contact state
		$subquery .= ' AND parent.published != 1 GROUP BY cat.id ';

		// Select state to unpublished if up-path category is unpublished
		$publishedWhere = 'CASE WHEN badcats.id is null THEN a.published ELSE 0 END';
		$query->join('LEFT OUTER', '(' . $subquery . ') AS badcats ON badcats.id = c.id');

		// Filter by state
		$state = $this->getState('filter.published');

		if (is_numeric($state))
		{
			$query->where('a.published = ' . (int) $state);

			// Filter by start and end dates.
			$nullDate = $db->quote($db->getNullDate());
			$date     = JFactory::getDate();
			$nowDate  = $db->quote($date->toSql());
			$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')')
				->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')')
				->where($publishedWhere . ' = ' . (int) $state);
		}

		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')')
				->where('c.access IN (' . $groups . ')');
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
		$mstatus = $this->getState('filter.mstatus');

		if ($mstatus)
		{
			$query->where('a.mstatus = ' . $mstatus);
		}

		// Filter by language
		if ($this->getState('filter.language'))
		{
			$query->where('a.language in (' . $db->q(JFactory::getLanguage()->getTag()) . ',' . $db->q('*') . ')');
		}

		$order = $this->getState('filter.order', 'a.id');

		// Set sortname ordering if selected
		$query->order($db->escape($order) . ' ' . $db->escape('ASC'));

		return $query;
	}

	/**
	 * Export
	 *
	 * @param   string  $type    Type of export
	 * @param   string  $report  Name of report for file.
	 *
	 * @since 1.7.10
	 *
	 * @return void
	 */
	public function getExport($type, $report)
	{
		// Check for request forgeries.
		JSession::checkToken('get') or JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$params = JComponentHelper::getParams('com_churchdirectory');
		$this->setState('filter.published', '1');
		$this->populateState();

		$reportBuild = new ChurchDirectoryReportbuild;
		$items  = $this->_db->setQuery($this->getListQuery())->loadObjectList();


		// Prepare the data.
		for ($i = 0, $n = count($items); $i < $n; $i++)
		{
			$item = &$items[$i];

			// Compute the contact slug.
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;

			$item->event = new stdClass;
			$temp        = new Registry;
			$temp->loadString($item->params);
			$item->params = clone $params;
			$item->params->merge($temp);

			// Build Cat params
			$reg = new Joomla\Registry\Registry;
			$reg->loadString($item->category_params);
			$item->category_params = $reg;

			if ($item->params->get('dr_show_email', 0) == 1)
			{
				$item->email_to = trim($item->email_to);

				if (empty($item->email_to) && !JMailHelper::isEmailAddress($item->email_to))
				{
					$item->email_to = null;
				}
			}
		}

		switch ($type)
		{
			case 'csv':
				$reportBuild->getCsv($items, $report);
				break;
			case 'kml':
				$reportBuild->getKML($items, $report);
				break;
			case 'pdf':
				$reportBuild->getPDF($items, $report);
				break;
		}

		return;
	}
}
