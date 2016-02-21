<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

/**
 * Class view cpanel
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
	 */
	protected $items;

	/**
	 * Protect Pragination
	 *
	 * @var array
	 */
	protected $pagination;

	/**
	 * Protect State
	 *
	 * @var object
	 */
	protected $state;

	protected $sidebar;

	/**
	 * Display Function
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed
	 */
	public function display($tpl = null)
	{
		ChurchDirectoryHelper::addSubmenu('reports');

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
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_REPORTS'), 'churchdirectory.png');

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::preferences('com_churchdirectory');
			JToolBarHelper::divider();
		}

		JToolBarHelper::help('churchdirectory', true);
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
