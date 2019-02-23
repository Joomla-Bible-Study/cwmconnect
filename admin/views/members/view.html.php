<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View class for a list of churchdirectories.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewMembers extends JViewLegacy
{
	/**
	 * Protect items
	 *
	 * @var array
	 * @since    1.7.0
	 */
	protected $items;

	/**
	 * Protect pagination
	 *
	 * @var object
	 * @since    1.7.0
	 */
	protected $pagination;

	/**
	 * Protect state
	 *
	 * @var object
	 * @since    1.7.0
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  JForm
	 * @since    1.7.0
	 */
	public $filterForm;

	/**
	 * The active search filters
	 *
	 * @var  array
	 * @since    1.7.0
	 */
	public $activeFilters;

	/**
	 * The sidebar markup
	 *
	 * @var  string
	 * @since    1.7.0
	 */
	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since    1.7.0
	 * @throws   \Exception
	 */
	public function display($tpl = null)
	{
		// Assign data to the view
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		ChurchDirectoryHelper::addSubmenu('members');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		// Preprocess the list of items to find ordering divisions.
		// TODO: Complete the ordering stuff with nested sets
		foreach ($this->items as &$item)
		{
			$item->order_up = true;
			$item->order_dn = true;
		}

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			// Set the toolbar
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
		}

		// Display the template
		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since    1.7.0
	 * @return void
	 */
	protected function addToolbar()
	{
		$canDo = ChurchDirectoryHelper::getActions('com_churchdirectory', 'category', $this->state->get('filter.category_id'));
		JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
		$user  = JFactory::getUser();

		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_MEMBERS'), 'address contact');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create'))) > 0)
		{
			JToolbarHelper::addNew('member.add');
		}

		if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
		{
			JToolbarHelper::editList('member.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('members.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('members.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::custom('members.featured', 'featured.png', 'featured_f2.png', 'JFEATURE', true);
			JToolbarHelper::custom('members.unfeatured', 'unfeatured.png', 'featured_f2.png', 'JUNFEATURE', true);
			JToolbarHelper::archiveList('members.archive');
			JToolbarHelper::checkin('members.checkin');
		}

		// Add a batch button
		if ($user->authorise('core.create', 'com_churchdirectory')
			&& $user->authorise('core.edit', 'com_churchdirectory')
			&& $user->authorise('core.edit.state', 'com_churchdirectory'))
		{
			$title = JText::_('JTOOLBAR_BATCH');

			// Instantiate a new JLayoutFile instance and render the batch button
			$layout = new JLayoutFile('joomla.toolbar.batch');

			$dhtml = $layout->render(array('title' => $title));
			JToolbar::getInstance('toolbar')->appendButton('Custom', $dhtml, 'batch');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'members.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('members.trash');
		}

		if ($user->authorise('core.admin', 'com_churchdirectory') || $user->authorise('core.options', 'com_churchdirectory'))
		{
			JToolbarHelper::preferences('com_churchdirectory');
		}

		JToolbarHelper::help('churchdirectory_members', true);
		JHtmlSidebar::setAction('index.php?option=com_churchdirectory&amp;view=members');
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		return [
			'a.ordering'     => JText::_('JGRID_HEADING_ORDERING'),
			'a.state'        => JText::_('JSTATUS'),
			'a.name'         => JText::_('JGLOBAL_TITLE'),
			'category_title' => JText::_('JCATEGORY'),
			'ul.name'        => JText::_('COM_CHURCHDIRECTORY_FIELD_LINKED_USER_LABEL'),
			'a.featured'     => JText::_('JFEATURED'),
			'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
			'a.language'     => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id'           => JText::_('JGRID_HEADING_ID')
		];
	}
}
