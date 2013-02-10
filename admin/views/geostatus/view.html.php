<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
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
	 */
	protected $pagination;

	/**
	 * Protect state
	 *
	 * @var object
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
	 */
	public function display($tpl = null)
	{
		// Assign data to the view

		$this->items      = array_merge($this->get('Items'), $this->get('GeoErrors'));
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');

		ChurchDirectoryHelper::addSubmenu('geostatus');

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

		// Set the toolbar title
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE_STATUS'), 'churchdirectory');

		JToolBarHelper::custom('geoupdate.fix', 'refresh', 'refresh', 'COM_CHURCHDIRECTORY_GEOUPDATE', false, false);

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_churchdirectory');
		}

		JToolBarHelper::help('churchdirectory_geoupdate', true);
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
			'ul.name'        => JText::_('COM_CHURCHDIRECTORY_FIELD_LINKED_USER_LABEL'),
			'a.featured'     => JText::_('JFEATURED'),
			'a.access'       => JText::_('JGRID_HEADING_ACCESS'),
			'a.language'     => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id'           => JText::_('JGRID_HEADING_ID')
		);
	}

}
