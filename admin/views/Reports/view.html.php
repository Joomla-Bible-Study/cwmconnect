<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

/**
 * Class view Reports
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewReports extends JViewLegacy
{
	/**
	 * Protect Items
	 *
	 * @var array
	 * @since    1.7.0
	 */
	protected $items;

	/**
	 * Protect Pragination
	 *
	 * @var array
	 * @since    1.7.0
	 */
	protected $pagination;

	/**
	 * Protect State
	 *
	 * @var object
	 * @since    1.7.0
	 */
	protected $state;

	protected $sidebar;

	/**
	 * Display Function
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed
	 *
	 * @since    1.7.0
	 */
	public function display($tpl = null)
	{
		ChurchDirectoryHelper::addSubmenu('reports');

		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');

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
	 * @return void
	 */
	protected function addToolbar()
	{
		$canDo = ChurchDirectoryHelper::getActions();
		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_REPORTS'), 'churchdirectory.png');

		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::divider();
			JToolbarHelper::preferences('com_churchdirectory');
			JToolbarHelper::divider();
		}

		JToolbarHelper::help('churchdirectory', true);
	}

	/**
	 * Set browser title
	 *
	 * @since 1.7.0
	 * @return void
	 */
	protected function setDocument()
	{
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_CHURCHDIRECTORY_REPORTS'));
	}
}
