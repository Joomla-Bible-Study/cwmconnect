<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View class for a list of church directories.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewFamilyUnits extends JViewLegacy
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
	 * @var array
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

	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  ?
	 *
	 * @return    mixed
	 *
	 * @since    1.7.0
	 */
	public function display($tpl = null)
	{
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		ChurchDirectoryHelper::addSubmenu('familyunits');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		// Set the toolbar
		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();

		// Set the document
		$this->setDocument();

		// Display the template
		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @since    1.7.0
	 *
	 * @return void
	 */
	protected function addToolbar()
	{
		$canDo = ChurchDirectoryHelper::getActions('com_churchdirectory', 'familyunit');
		$user  = JFactory::getUser();
		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_FAMILYUNITS'), 'churchdirectory');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create'))) > 0)
		{
			JToolbarHelper::addNew('familyunit.add');
		}

		if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
		{
			JToolbarHelper::editList('familyunit.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::divider();
			JToolbarHelper::publish('familyunits.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('familyunits.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::divider();
			JToolbarHelper::checkin('familyunits.checkin');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'familyunits.delete', 'JTOOLBAR_EMPTY_TRASH');
			JToolbarHelper::divider();
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('familyunits.trash');
			JToolbarHelper::divider();
		}

		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_churchdirectory');
			JToolbarHelper::divider();
		}

		JToolbarHelper::help('churchdirectory_familyunit', true);
		JHtmlSidebar::setAction('index.php?option=com_churchdirectory&amp;view=familyunits');
	}

	/**
	 * Set browser title
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_CHURCHDIRECTORY_FAMILYUNITS'));
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
			'a.state'    => JText::_('JSTATUS'),
			'a.name'     => JText::_('JGLOBAL_TITLE'),
			'a.access'   => JText::_('JGRID_HEADING_ACCESS'),
			'a.language' => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id'       => JText::_('JGRID_HEADING_ID')
		];
	}
}
