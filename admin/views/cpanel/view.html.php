<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

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
	 *
	 * @since 1.7.0
	 */
	protected $items;

	/**
	 * Protect Pragination
	 *
	 * @var array
	 *
	 * @since 1.7.0
	 */
	protected $pagination;

	/**
	 * Protect State
	 *
	 * @var object
	 *
	 * @since 1.7.0
	 */
	protected $state;

	protected $sidebar;

	protected $xml;

	/**
	 * Display Function
	 *
	 * @param   string  $tpl  ?
	 *
	 * @return mixed
	 *
	 * @since 1.7.0
	 */
	public function display($tpl = null)
	{
		$this->items      = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state      = $this->get('State');
		$component = JPATH_ADMINISTRATOR . '/components/com_churchdirectory/churchdirectory.xml';

		if (file_exists($component))
		{
			$this->xml = simplexml_load_file($component);
		}

		ChurchDirectoryHelper::addSubmenu('cpanel');

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
		$canDo = ChurchDirectoryHelper::getActions('com_churchdirectory');
		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_CPANEL'), 'churchdirectory.png');

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
		$document->setTitle(JText::_('COM_CHURCHDIRECTORY_ADMINISTRATION'));
	}
}
