<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_COMPONENT_ADMINISTRATOR . '/liveupdate/liveupdate.php';

/**
 * Class view cpanel
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewCpanel extends JViewLegacy
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

	/**
	 * Display Function
	 *
	 * @param   string  $tpl  ?
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');

		ChurchDirectoryHelper::addSubmenu('cpanel');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
		}

		// Set the toolbar
		$this->addToolbar();

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
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
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_CPANEL'), 'churchdirectory.png');

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
		$document->setTitle(JText::_('COM_CHURCHDIRECTORY_ADMINISTRATION'));
	}

}
