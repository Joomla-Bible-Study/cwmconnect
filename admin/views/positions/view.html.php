<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;


jimport('joomla.application.component.helper');
jimport('joomla.i18n.help');

/**
 * View class for a list of Positions.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewPositions extends JViewLegacy
{

	/**
	 * Protect items
	 *
	 * @var array protect items
	 */
	protected $items;

	/**
	 * Protect items
	 *
	 * @var array protect pagination
	 */
	protected $pagination;

	/**
	 * Protect state
	 *
	 * @var array protect state
	 */
	protected $state;

	/**
	 * Display the view
	 *
	 * @param string $tpl
	 *
	 * @return    mixed
	 */
	public function display($tpl = null)
	{
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');

		ChurchDirectoryHelper::addSubmenu('positions');

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
	 *
	 * @return void
	 */
	protected function addToolbar()
	{
		$canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_POSITIONS'), 'churchdirectory');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create'))) > 0)
		{
			JToolBarHelper::addNew('position.add');
		}

		if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
		{
			JToolBarHelper::editList('position.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::publish('positions.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish('positions.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolBarHelper::divider();
			JToolBarHelper::checkin('positions.checkin');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'positions.delete', 'JTOOLBAR_EMPTY_TRASH');
			JToolBarHelper::divider();
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::trash('positions.trash');
			JToolBarHelper::divider();
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_churchdirectory');
			JToolBarHelper::divider();
		}

		JToolBarHelper::help('churchdirectory_position', true);
	}

	/**
	 * Set Document title
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_CHURCHDIRECTORY_POSITIONS'));
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
			'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
			'a.language'     => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id'           => JText::_('JGRID_HEADING_ID')
		);
	}

}
