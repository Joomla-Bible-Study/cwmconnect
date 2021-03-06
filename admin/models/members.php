<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of Member records.
 *
 * @package  ChurchDirectory
 * @since    1.7.0
 */
class ChurchDirectoryModelMembers extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.7.0
	 */
	public function __construct($config = [])
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'id', 'a.id',
				'name', 'a.name',
				'lname', 'a.lname',
				'funitid', 'funitname',
				'alias', 'a.alias',
				'checked_out', 'a.checked_out',
				'checked_out_time', 'a.checked_out_time',
				'catid', 'a.catid', 'category_title',
				'user_id', 'a.user_id',
				'state', 'a.state',
				'postcode', 'a.postcode',
				'access', 'a.access', 'access_level',
				'created', 'a.created',
				'created_by', 'a.created_by',
				'ordering', 'a.ordering',
				'featured', 'a.featured',
				'language', 'a.language',
				'publish_up', 'a.publish_up',
				'publish_down', 'a.publish_down',
				'ul.name', 'linked_user',
				'mstatus', 'a.mstatus',
			];

			$assoc = JLanguageAssociations::isEnabled();

			if ($assoc)
			{
				$config['filter_fields'][] = 'association';
			}
		}

		parent::__construct($config);
	}

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
	protected function populateState($ordering = 'a.name', $direction = 'asc')
	{
		$app = JFactory::getApplication();

		$forcedLanguage = $app->input->get('forcedLanguage', '', 'cmd');

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		// Adjust the context to support forced languages.
		if ($forcedLanguage)
		{
			$this->context .= '.' . $forcedLanguage;
		}

		$this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string'));
		$this->setState('filter.published', $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string'));
		$this->setState('filter.category_id', $this->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id', '', 'string'));
		$this->setState('filter.access', $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', '', 'cmd'));
		$this->setState('filter.language', $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '', 'string'));
		$this->setState('filter.tag', $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '', 'string'));
		$this->setState('filter.level', $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', null, 'int'));
		$this->setState('filter.mstatus', $this->getUserStateFromRequest($this->context . '.filter.mstatus', 'filter_mstatus', '', 'string'));

		// List state information.
		parent::populateState($ordering, $direction);

		// Force a language.
		if (!empty($forcedLanguage))
		{
			$this->setState('filter.language', $forcedLanguage);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return    string        A store id.
	 *
	 * @since    1.7.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.category_id');
		$id .= ':' . serialize($this->getState('filter.access'));
		$id .= ':' . $this->getState('filter.language');
		$id .= ':' . $this->getState('filter.mstatus');
		$id .= ':' . $this->getState('filter.tag');
		$id .= ':' . $this->getState('filter.level');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.7.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);
		$user  = JFactory::getUser();

		// Select the required fields from the table.
		$query->select(
			$db->qn(
				explode(', ', $this->getState(
					'list.select',
					'a.id, a.name, a.lname, a.funitid, a.alias, a.checked_out, a.checked_out_time, a.catid, a.user_id' .
					', a.published, a.access, a.created, a.created_by, a.ordering, a.featured, a.language, a.mstatus' .
					', a.image, a.publish_up, a.publish_down'
					)
				)
			)
		);
		$query->from($db->qn('#__churchdirectory_details', 'a'));

		// Join over the users for the linked user.
		$query->select([$db->qn('ul.name', 'linked_user'),$db->quoteName('ul.email')]);
		$query->join('LEFT', $db->qn('#__users', 'ul') . ' ON ' . $db->qn('ul.id') . ' = ' . $db->qn('a.user_id'));

		// Join over the Family Units.
		$query->select($db->qn('fu.name', 'funitname'));
		$query->join('LEFT', $db->qn('#__churchdirectory_familyunit', 'fu') . ' ON ' . $db->qn('fu.id') . ' = ' . $db->qn('a.funitid'));

		// Join over the language
		$query->select($db->qn('l.title', 'language_title'));
		$query->join('LEFT', $db->qn('#__languages', 'l') . ' ON ' . $db->qn('l.lang_code') . ' = ' . $db->qn('a.language'));

		// Join over the users for the checked out user.
		$query->select($db->qn('uc.name', 'editor'));
		$query->join('LEFT', $db->qn('#__users', 'uc') . ' ON ' . $db->qn('uc.id') . ' = ' . $db->qn('a.checked_out'));

		// Join over the asset groups.
		$query->select($db->qn('ag.title', 'access_level'));
		$query->join('LEFT', $db->qn('#__viewlevels', 'ag') . ' ON ' . $db->qn('ag.id') . ' = ' . $db->qn('a.access'));

		// Join over the categories.
		$query->select($db->qn('c.title', 'category_title'));
		$query->join('LEFT', $db->qn('#__categories', 'c') . ' ON ' . $db->qn('c.id') . ' = ' . $db->qn('a.catid'));

		// Join over the associations.
		$assoc = JLanguageAssociations::isEnabled();

		if ($assoc)
		{
			$query->select('COUNT(' . $db->quoteName('asso2.id') . ') > 1 as ' . $db->quoteName('association'))
				->join(
					'LEFT',
					$db->quoteName('#__associations', 'asso') . ' ON ' . $db->quoteName('asso.id') . ' = ' . $db->quoteName('a.id')
					. ' AND ' . $db->quoteName('asso.context') . ' = ' . $db->quote('com_contact.item')
				)
				->join(
					'LEFT',
					$db->quoteName('#__associations', 'asso2') . ' ON ' . $db->quoteName('asso2.key') . ' = ' . $db->quoteName('asso.key')
				)
				->group(
					$db->quoteName(
						array(
							'a.id',
							'a.name',
							'a.alias',
							'a.checked_out',
							'a.checked_out_time',
							'a.catid',
							'a.user_id',
							'a.published',
							'a.access',
							'a.created',
							'a.created_by',
							'a.ordering',
							'a.featured',
							'a.language',
							'a.publish_up',
							'a.publish_down',
							'ul.name' ,
							'ul.email',
							'l.title' ,
							'l.image' ,
							'uc.name' ,
							'ag.title' ,
							'c.title',
							'c.level'
						)
					)
				);
		}

		// Filter by access level.
		if ($access = $this->getState('filter.access'))
		{
			$query->where($db->qn('a.access') . ' = ' . (int) $access);
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where($db->qn('a.access') . ' IN (' . $groups . ')');
		}

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where($db->qn('a.published') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(' . $db->qn('a.published') . ' = 0 OR' . $db->qn('a.published') . ' = 1)');
		}

		// Filter by a single or group of categories.
		$categoryId = $this->getState('filter.category_id');

		if (is_numeric($categoryId))
		{
			$query->where($db->qn('a.catid') . ' = ' . (int) $categoryId);
		}
		elseif (is_array($categoryId))
		{
			$query->where($db->qn('a.catid') . ' IN (' . implode(',', ArrayHelper::toInteger($categoryId)) . ')');
		}

		// Filter by search in name.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'author:') === 0)
			{
				$search = $db->q('%' . str_replace(' ', '%', $db->escape(trim(substr($search, 7)), true) . '%'));
				$query->where('(uc.name LIKE ' . $search . ' OR uc.username LIKE ' . $search . ')');
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

		// Filter on the language.
		if ($language = $this->getState('filter.language'))
		{
			$query->where($db->qn('a.language') . ' = ' . $db->q($language));
		}

		// Filter on the language.
		if ($mstatus = $this->getState('filter.mstatus'))
		{
			$query->where('a.mstatus = ' . $db->q($mstatus));
		}

		// Filter by a single tag.
		$tagId = $this->getState('filter.tag');

		if (is_numeric($tagId))
		{
			$query->where($db->quoteName('tagmap.tag_id') . ' = ' . (int) $tagId)
				->join(
					'LEFT',
					$db->quoteName('#__contentitem_tag_map', 'tagmap')
					. ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
					. ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_churchdirectory.member')
				);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.name');
		$orderDirn = $this->state->get('list.direction', 'asc');

		if ($orderCol == 'a.ordering' || $orderCol == 'category_title')
		{
			$orderCol = $db->qn('c.title ') . $orderDirn . ', ' . $db->qn('a.ordering');
		}

		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}
}
