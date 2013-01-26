<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * For Getting GeoUpdate Status from Google
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryModelGeoStatus extends JModelList
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return    void
	 *
	 * @since    1.7.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Adjust the context to support modal layouts.
		if ($layout = JFactory::getApplication()->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		$categoryId = $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
		$this->setState('filter.category_id', $categoryId);

		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		// List state information.
		parent::populateState('a.name', 'asc');
	}

	/**
	 * Overrides Pagination
	 *
	 * @return boolean
	 */
	public function getPagination()
	{
		return false;
	}

	/**
	 * Get Geo Errors
	 *
	 * @return mixed
	 */
	public function getGeoErrors()
	{

		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select('u.*, m.*')->from('#__churchdirectory_details AS m');
		$query->leftJoin('#__churchdirectory_geoupdate AS u ON m.id = u.member_id ');
		$query->where('m.id = u.member_id');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get Geo Errors
	 *
	 * @return mixed
	 */
	public function getNoGeoInfo()
	{
		$results = array();
		$db      = $this->getDbo();
		$query   = $db->getQuery(true);
		$query->select('*')->from('#__churchdirectory_details');
		$query->where('lat = ' . 0.000000);
		$query->where('lng = ' . 0.000000);
		$db->setQuery($query);
		$nogeo = $db->loadObjectList();

		foreach ($nogeo AS $key => $member)
		{
			$member->status = 'No Geo Location Set ';
			$results{$key}  = $member;
		}

		return $results;
	}


	/**
	 * Get Geo Errors and Member without Geo Location Data.
	 *
	 * @return mixed
	 */
	public function getInfo()
	{
		return array_merge($this->getGeoErrors(), $this->getNoGeoInfo());
	}
}
