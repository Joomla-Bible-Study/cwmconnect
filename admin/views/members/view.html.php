<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
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
	 */
	protected $items;

	/**
	 * Protect pagination
	 *
	 * @var object
	 */
	protected $pagination;

	/**
	 * Protect state
	 *
	 * @var object
	 */
	protected $state;

	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{

		// Assign data to the view
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');

		ChurchDirectoryHelper::addSubmenu('members');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		// Set the toolbar
		$this->addToolbar();

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		// Set the document
		$this->setDocument();

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
		$canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));
		$user  = JFactory::getUser();

		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');

		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_MEMBERS'), 'members');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create'))) > 0)
		{
			JToolBarHelper::addNew('member.add');
		}

		if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
		{
			JToolBarHelper::editList('member.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::publish('members.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish('members.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::archiveList('members.archive');
			JToolBarHelper::checkin('members.checkin');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'members.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('members.trash');
		}
		if (version_compare(JVERSION, '3.0.0', 'ge'))
		{
			// Add a batch button
			if ($user->authorise('core.edit'))
			{
				JHtml::_('bootstrap.modal', 'collapseModal');
				$title = JText::_('JTOOLBAR_BATCH');
				$dhtml = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small\">
						<i class=\"icon-checkbox-partial\" title=\"$title\"></i>
						$title</button>";
				$bar->appendButton('Custom', $dhtml, 'batch');
			}
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_churchdirectory');
		}

		JToolBarHelper::help('churchdirectory_members', true);

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			JHtmlSidebar::setAction('index.php?option=com_churchdirectory&amp;view=members');

			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);

			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_CATEGORY'),
				'filter_category_id',
				JHtml::_('select.options', JHtml::_('category.options', 'com_contact'), 'value', 'text', $this->state->get('filter.category_id'))
			);

			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_ACCESS'),
				'filter_access',
				JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
			);

			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_LANGUAGE'),
				'filter_language',
				JHtml::_('select.options', JHtml::_('contentlanguage.existing', true, true), 'value', 'text', $this->state->get('filter.language'))
			);
		}
	}

	/**
	 * Set Document title
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_CHURCHDIRECTORY_ADMINISTRATION_MEMBERS'));
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
		return array(
			'a.ordering'     => JText::_('JGRID_HEADING_ORDERING'),
			'a.state'        => JText::_('JSTATUS'),
			'a.name'         => JText::_('JGLOBAL_TITLE'),
			'category_title' => JText::_('JCATEGORY'),
			'ul.name'        => JText::_('COM_CONTACT_FIELD_LINKED_USER_LABEL'),
			'a.featured'     => JText::_('JFEATURED'),
			'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
			'a.language'     => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id'           => JText::_('JGRID_HEADING_ID')
		);
	}

}
