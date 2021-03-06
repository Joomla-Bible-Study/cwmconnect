<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View to fix Database.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewDatabase extends JViewLegacy
{
	protected $changeSet;

	protected $errors;

	protected $results;

	protected $schemaVersion;

	protected $updateVersion;

	protected $filterParams;

	protected $pagination;

	protected $errorCount;

	protected $jversion;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return    mixed
	 *
	 * @since 1.7.0
	 */
	public function display($tpl = null)
	{
		$language = JFactory::getLanguage();
		$language->load('com_installer');

		ChurchDirectoryHelper::addSubmenu('database');

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			$this->sidebar = JHtmlSidebar::render();
		}

		// Get data from the model for database
		$this->changeSet     = $this->get('Items');
		$this->errors        = $this->changeSet->check();
		$this->results       = $this->changeSet->getStatus();
		$this->schemaVersion = $this->get('SchemaVersion');
		$this->updateVersion = $this->get('UpdateVersion');
		$this->filterParams  = $this->get('DefaultTextFilters');
		$this->schemaVersion = ($this->schemaVersion) ? $this->schemaVersion : JText::_('JNONE');
		$this->updateVersion = ($this->updateVersion) ? $this->updateVersion : JText::_('JNONE');
		$this->pagination    = $this->get('Pagination');
		$this->errorCount    = count($this->errors);
		$this->jversion      = $this->get('CompVersion');

		// End for database
		$errors = count($this->errors);

		if (!(strncmp($this->schemaVersion, $this->jversion, 5) === 0))
		{
			$this->errorCount++;
		}

		if (!$this->filterParams)
		{
			$this->errorCount++;
		}

		if (($this->updateVersion != $this->jversion))
		{
			$this->errorCount++;
		}

		$this->setLayout('form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		// Set the toolbar
		$this->addToolbar();

		// Display the template
		return parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.7.0
	 */
	protected function addToolbar()
	{
		$canDo = ChurchDirectoryHelper::getActions();

		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_DATABASE'), 'puzzle install');
		JToolbarHelper::divider();

		if ($canDo)
		{
			JToolbarHelper::custom('database.fix', 'refresh', 'refresh', 'COM_CHURCHDIRECTORY_DATABASE_FIX', false);
		}
	}
}
