<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View class for a list of Positions.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewPositions extends JViewLegacy
{
	public $filterForm;

	public $activeFilters;

	/**
	 * Protect items
	 *
	 * @var array protect items
	 * @since    1.7.0
	 */
	protected $items;

	/**
	 * Protect items
	 *
	 * @var object protect pagination
	 * @since    1.7.0
	 */
	protected $pagination;

	/**
	 * Protect state
	 *
	 * @var object protect state
	 * @since    1.7.0
	 */
	protected $state;

	protected $sidebar;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
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

		ChurchDirectoryHelper::addSubmenu('positions');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		// Preprocessors the list of items to find ordering divisions.
		// TODO: Complete the ordering stuff with nested sets
		foreach ($this->items as &$item)
		{
			$item->order_up = true;
			$item->order_dn = true;
		}

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
		}
		else
		{
			// In article associations modal we need to remove language filter if forcing a language.
			// We also need to change the category filter to show show categories with All or the forced language.
			if ($forcedLanguage = JFactory::getApplication()->input->get('forcedLanguage', '', 'CMD'))
			{
				// If the language is forced we can't allow to select the language, so transform the language selector filter into an hidden field.
				$languageXml = new SimpleXMLElement('<field name="language" type="hidden" default="' . $forcedLanguage . '" />');
				$this->filterForm->setField($languageXml, 'filter', true);

				// Also, unset the active language filter so the search tools is not open by default with this filter.
				unset($this->activeFilters['language']);

				// One last changes needed is to change the category filter to just show categories with All language or with the forced language.
				$this->filterForm->setFieldAttribute('category_id', 'language', '*,' . $forcedLanguage, 'filter');
			}
		}

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
		$user  = JFactory::getUser();
		$canDo = ChurchDirectoryHelper::getActions('com_churchdirectory');

		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_POSITIONS'), 'churchdirectory');

		if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create'))) > 0)
		{
			JToolbarHelper::addNew('position.add');
		}

		if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own')))
		{
			JToolbarHelper::editList('position.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::publish('positions.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('positions.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::archiveList('positions.archive');
			JToolbarHelper::checkin('positions.checkin');
		}

		// Add a batch button
		if ($user->authorise('core.create', 'com_churchdirectory')
			&& $user->authorise('core.edit', 'com_churchdirectory')
			&& $user->authorise('core.edit.state', 'com_churchdirectory'))
		{
			$title = JText::_('JTOOLBAR_BATCH');

			// Instantiate a new JLayoutFile instance and render the batch button
			$layout = new JLayoutFile('joomla.toolbar.batch');

			$dhtml = $layout->render(array('title' => $title));
			JToolbar::getInstance('toolbar')->appendButton('Custom', $dhtml, 'batch');
		}

		if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete'))
		{
			JToolbarHelper::deleteList('', 'positions.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('positions.trash');
		}

		if ($canDo->get('core.admin', 'com_churchdirectory') || $user->authorise('core.options', 'com_churchdirectory'))
		{
			JToolbarHelper::preferences('com_churchdirectory');
		}

		JToolbarHelper::help('churchdirectory_position', true);

		JHtmlSidebar::setAction('index.php?option=com_churchdirectory&amp;view=positions');
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
			'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'a.state'    => JText::_('JSTATUS'),
			'a.name'     => JText::_('JGLOBAL_TITLE'),
			'a.access'   => JText::_('JGRID_HEADING_ACCESS'),
			'a.language' => JText::_('JGRID_HEADING_LANGUAGE'),
			'a.id'       => JText::_('JGRID_HEADING_ID')
		];
	}
}
