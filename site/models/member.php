<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

jimport('joomla.event.dispatcher');

/**
 * Module for Members
 *
 * @package  ChurchDirectory.Site
 * @since    2.5
 */
class ChurchDirectoryModelMember extends JModelForm
{
	/**
	 * Protect view
	 *
	 * @var string
	 * @since       1.7.2
	 */
	protected $view_item = 'member';

	/**
	 * Protect item
	 *
	 * @var int
	 * @since       1.7.2
	 */
	protected $item = null;

	protected $member;

	/**
	 * Model context string.
	 *
	 * @var        string
	 * @since       1.7.2
	 */
	protected $context = 'com_churchdirectory.member';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since    1.6
	 * @return  void
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication('site');

		// Load state from the request.
		$pk = $app->input->getInt('id');
		$this->setState('member.id', $pk);

		// Load the parameters.
		$params = $app->getParams();
		$this->setState('params', $params);
		$user = JFactory::getUser();

		if ((!$user->authorise('core.edit.state', 'com_churchdirectory')) && (!$user->authorise('core.edit', 'com_churchdirectory')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}
	}

	/**
	 * Method to get the member form.
	 *
	 * The base form is loaded from XML and then an event is fired
	 *
	 * @param   array    $data      An optional array of data for the form to interrogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return   bool|JForm    A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = [], $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_churchdirectory.member', 'member', ['control' => 'jform', 'load_data' => true]);

		if (empty($form))
		{
			return false;
		}

		$id = (int) $this->getState('member.id');

		if ($id)
		{
			$params = $this->getState('params');
			$member = $this->item[$id];
			$params->merge($member->params);

			if (!$params->get('show_email_copy', 0))
			{
				$form->removeField('member_email_copy');
			}
		}

		return $form;
	}

	/**
	 * Load form date
	 *
	 * @return array
	 *
	 * @since       1.7.2
	 */
	protected function loadFormData()
	{
		$data = (array) JFactory::getApplication()->getUserState('com_churchdirectory.member.data', []);

		$this->preprocessData('com_churchdirectory.member', $data);

		return $data;
	}

	/**
	 * Gets a list of members
	 *
	 * @param   int  $pk  Id of member
	 *
	 * @return mixed Object or null
	 *
	 * @since       1.7.2
	 */
	public function &getItem($pk = null)
	{
		// Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('member.id');

		if ($this->item === null)
		{
			$this->item = [];
		}

		if (!isset($this->item[$pk]))
		{
			try
			{
				$app   = JFactory::getApplication();
				$db    = $this->getDbo();
				$query = $db->getQuery(true);

				// Sqlsrv changes
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
					->from('#__churchdirectory_details AS a')

					// Join on category table.
					->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access')
					->join('LEFT', '#__categories AS c on c.id = a.catid')

					// Join over the categories to get parent category titles
					->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
					->join('LEFT', '#__categories as parent ON parent.id = c.parent_id')

					// Join over the family Unit to get info
					->select('fu.name as fu_name, fu.id as fu_id, fu.description as fu_description')
					->join('LEFT', '#__churchdirectory_familyunit AS fu ON fu.id = a.funitid')

					->where('a.id = ' . (int) $pk);

				// Filter by start and end dates.
				$nullDate = $db->q($db->getNullDate());
				$nowDate  = $db->q(JFactory::getDate()->toSql());

				// Filter by published state.
				$published = $this->getState('filter.published');
				$archived  = $this->getState('filter.archived');

				if (is_numeric($published))
				{
					$query->where('(a.published = ' . (int) $published . ' OR a.published =' . (int) $archived . ')');
					$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
					$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
				}

				$db->setQuery($query);

				$data = $db->loadObject();

				if (empty($data))
				{
					$app->enqueueMessage(JText::_('COM_CHURCHDIRECTORY_ERROR_MEMBER_NOT_FOUND'), 'error');

					return $this->item[$pk];
				}

				// Check for published state if filter set.
				if (((is_numeric($published)) || (is_numeric($archived))) && (($data->published != $published) && ($data->published != $archived)))
				{
					$app->enqueueMessage(JText::_('COM_CHURCHDIRECTORY_ERROR_MEMBER_NOT_FOUND'), 'error');
				}

				// Convert parameter fields to objects.
				$registry = new Registry;
				$registry->loadString($data->params);
				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				$registry = new Registry;
				$registry->loadString($data->metadata);
				$data->metadata = $registry;

				$registry = new Registry;
				$registry->loadString($data->attribs);
				$data->attribs = $registry;

				// Compute access permissions.
				if ($access = $this->getState('filter.access'))
				{
					// If the access filter has been set, we already know this user can view.
					$data->params->set('access-view', true);
				}
				else
				{
					// If no access filter is set, the layout takes some responsibility for display of limited information.
					$user   = JFactory::getUser();
					$groups = $user->getAuthorisedViewLevels();

					if ($data->catid == 0 || $data->category_access === null)
					{
						$data->params->set('access-view', in_array($data->access, $groups));
					}
					else
					{
						$data->params->set('access-view', in_array($data->access, $groups) && in_array($data->category_access, $groups));
					}
				}

				$this->item[$pk] = $data;
			}
			catch (Exception $e)
			{
				$this->setError($e);
				$this->item[$pk] = false;
			}
		}

		if ($this->item[$pk])
		{
			if ($extendedData = $this->getChurchDirectoryQuery($pk))
			{
				$this->item[$pk]->articles = $extendedData->articles;
				$this->item[$pk]->profile  = $extendedData->profile;
			}
		}

		return $this->item[$pk];
	}

	/**
	 * Get ChurchDirectory query
	 *
	 * @param   int  $pk  ID of church member
	 *
	 * @return object|boolean
	 *
	 * @throws Exception
	 * @throws JException
	 * @since       1.7.2
	 */
	protected function getChurchDirectoryQuery($pk = null)
	{
		// TODO: Cache on the fingerprint of the arguments
		$db   = $this->getDbo();
		$user = JFactory::getUser();
		$pk   = (!empty($pk)) ? $pk : (int) $this->getState('member.id');
		$query = $db->getQuery(true);
		$result = null;

		if ($pk)
		{
			// Sqlsrv changes
			$case_when = ' CASE WHEN ';
			$case_when .= $query->charLength('a.alias', '!=', '0');
			$case_when .= ' THEN ';
			$a_id = $query->castAsChar('a.id');
			$case_when .= $query->concatenate([$a_id, 'a.alias'], ':');
			$case_when .= ' ELSE ';
			$case_when .= $a_id . ' END as slug';

			$case_when1 = ' CASE WHEN ';
			$case_when1 .= $query->charLength('cc.alias', '!=', '0');
			$case_when1 .= ' THEN ';
			$c_id = $query->castAsChar('cc.id');
			$case_when1 .= $query->concatenate([$c_id, 'cc.alias'], ':');
			$case_when1 .= ' ELSE ';
			$case_when1 .= $c_id . ' END as catslug';
			$query->select(
				'a.*, cc.access as category_access, cc.title as category_name, '
				. $case_when . ',' . $case_when1
			);

			$query->from('#__churchdirectory_details AS a');

			$query->join('INNER', '#__categories AS cc on cc.id = a.catid');

			$query->where('a.id = ' . (int) $pk);
			$published = $this->getState('filter.published');
			$archived  = $this->getState('filter.archived');

			if (is_numeric($published))
			{
				$query->where('a.published IN (1,2)');
				$query->where('cc.published IN (1,2)');
			}

			$groups = implode(',', $user->getAuthorisedViewLevels());
			$query->where('a.access IN (' . $groups . ')');

			try
			{
				$db->setQuery($query);
				$result = $db->loadObject();

				if (empty($result))
				{
					return false;
				}

				// If we are showing a member list, then the member parameters take priority
				// So merge the member parameters with the merged parameters
				if ($this->getState('params')->get('show_member_list'))
				{
					$registry = new Registry;
					$registry->loadString($result->params);
					$this->getState('params')->merge($registry);
				}
			}
			catch (Exception $e)
			{
				$this->setError($e);

				return false;
			}

			if ($result)
			{
				$user   = JFactory::getUser();
				$groups = implode(',', $user->getAuthorisedViewLevels());

				// Get the content by the linked user
				$query = $db->getQuery(true);
				$query->select('a.id');
				$query->select('a.title');
				$query->select('a.state');
				$query->select('a.access');
				$query->select('a.created');

				// SQL Server changes
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
				$query->select($case_when1 . ',' . $case_when);

				$query->from('#__content as a');
				$query->leftJoin('#__categories as c on a.catid=c.id');
				$query->where('created_by = ' . (int) $result->user_id);
				$query->where('a.access IN (' . $groups . ')');
				$query->order('a.state DESC, a.created DESC');

				// Filter per language if plugin published
				if (JFactory::getApplication()->getLanguageFilter())
				{
					$query->where(
						('a.created_by = ' . (int) $result->user_id) AND ('a.language=' . $db->quote(JFactory::getLanguage()->getTag()) .
							' OR a.language=' . $db->quote('*'))
					);
				}

				if (is_numeric($published))
				{
					$query->where('a.state IN (1,2)');
				}

				$db->setQuery($query, 0, 10);
				$articles         = $db->loadObjectList();
				$result->articles = $articles;

				// Get the profile information for the linked user
				require_once JPATH_ADMINISTRATOR . '/components/com_users/models/user.php';
				$userModel = JModelLegacy::getInstance('User', 'UsersModel', ['ignore_request' => true]);
				$data      = $userModel->getItem((int) $result->user_id);

				JPluginHelper::importPlugin('user');
				$form = new JForm('com_users.profile');

				// Get the dispatcher.
				$dispatcher = JEventDispatcher::getInstance();

				// Trigger the form preparation event.
				$dispatcher->trigger('onContentPrepareForm', [$form, $data]);

				// Trigger the data preparation event.
				$dispatcher->trigger('onContentPrepareData', ['com_users.profile', $data]);

				// Load the data into the form after the plugins have operated.
				$form->bind($data);
				$result->profile = $form;

				$this->member = $result;
			}
		}

		return $result;
	}

	/**
	 * Increment the hit counter for the contact.
	 *
	 * @param   int  $pk  Optional primary key of the article to increment.
	 *
	 * @return  boolean  True if successful; false otherwise and internal error set.
	 *
	 * @since   3.0
	 */
	public function hit($pk = 0)
	{
		$input    = JFactory::getApplication()->input;
		$hitcount = $input->getInt('hitcount', 1);

		if ($hitcount)
		{
			$pk = (!empty($pk)) ? $pk : (int) $this->getState('contact.id');
			$db = $this->getDbo();

			$db->setQuery(
				'UPDATE #__churchdirectory_details' .
				' SET hits = hits + 1' .
				' WHERE id = ' . (int) $pk
			);

			try
			{
				$db->execute();
			}
			catch (RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}
}
