<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Class for GeoUpdate
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.1
 */
class ChurchDirectoryViewGeoStatus extends JViewLegacy
{
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

	protected $items;

	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return    mixed
	 *
	 * @since    1.7.0
	 */
	public function display($tpl = null)
	{
		// Assign data to the view

		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		ChurchDirectoryHelper::addSubmenu('geostatus');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		// Set the toolbar
		$this->addToolbar();
		$this->sidebar = JHtmlSidebar::render();

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
		$canDo = ChurchDirectoryHelper::getActions();

		// Get the toolbar object instance
		$bar = JToolbar::getInstance('toolbar');

		// Set the toolbar title
		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE_STATUS'), 'geo');

		$bar->appendButton('Popup', 'refresh', 'COM_CHURCHDIRECTORY_GEOUPDATE',
			'index.php?option=com_churchdirectory&task=geoupdate.browse&tmpl=component', 550, 350
		);

		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_churchdirectory');
		}

		JToolbarHelper::help('churchdirectory_geoupdate', true);
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
