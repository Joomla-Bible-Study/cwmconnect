<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View to edit a position.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewPosition extends JViewLegacy
{
	/**
	 * Protect form
	 *
	 * @var array Protect form
	 * @since    1.7.0
	 */
	protected $form;

	/**
	 * Protect item
	 *
	 * @var object protect item
	 * @since    1.7.0
	 */
	protected $item;

	/**
	 * Protect state
	 *
	 * @var object protect state
	 * @since    1.7.0
	 */
	protected $state;

	protected $members;

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
		// Initialise variables.
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');

		$model         = $this->getModel();
		$this->members = $model->getMembers($this->item->id);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		// Set the toolbar
		$this->addToolbar();

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
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user       = JFactory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$canDo      = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));

		JToolbarHelper::title(
			$isNew ? JText::_('COM_CHURCHDIRECTORY_MANAGER_POSITION_NEW')
				: JText::_('COM_CHURCHDIRECTORY_MANAGER_POSITION_EDIT'), 'churchdirectory');

		// Build the actions for new and existing records.
		if ($isNew)
		{
			// For new records, check the create permission.
			if ($isNew && (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0))
			{
				JToolbarHelper::apply('position.apply');
				JToolbarHelper::save('position.save');
				JToolbarHelper::save2new('position.save2new');
			}

			JToolbarHelper::cancel('position.cancel');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolbarHelper::apply('position.apply');
					JToolbarHelper::save('position.save');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolbarHelper::save2new('position.save2new');
					}
				}
			}

			// If checked out, we can still save
			if ($canDo->get('core.create'))
			{
				JToolbarHelper::save2copy('position.save2copy');
			}

			JToolbarHelper::cancel('position.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
		JToolbarHelper::help('churchdirectory_position', true);
	}

	/**
	 * Set Document Title
	 *
	 * @return void
	 *
	 * @since    1.7.0
	 */
	protected function setDocument()
	{
		$isNew    = ($this->item->id < 1);
		$document = JFactory::getDocument();
		$document->setTitle(
			$isNew ? JText::_('COM_CHURCHDIRECTORY_POSITION_CREATING')
				: JText::sprintf('COM_CHURCHDIRECTORY_POSITION_EDITING', $this->item->name)
		);
	}
}
