<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View to edit a Family Unit.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewFamilyUnit extends JViewLegacy
{
	/**
	 * Protect form
	 *
	 * @var array
	 * @since    1.7.0
	 */
	protected $form;

	/**
	 * Protect item
	 *
	 * @var object
	 * @since    1.7.0
	 */
	protected $item;

	/**
	 * Protect state
	 *
	 * @var object
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

		$this->members = $this->get('members');

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
		$canDo      = ChurchDirectoryHelper::getActions('com_churchdirectory', 'familyunit', $this->item->id);

		JToolbarHelper::title(
			$isNew ? JText::_('COM_CHURCHDIRECTORY_MANAGER_FAMILYUNIT_NEW')
				: JText::_('COM_CHURCHDIRECTORY_MANAGER_FAMILYUNIT_EDIT'), 'churchdirectory');

		// Build the actions for new and existing records.
		if ($isNew)
		{
			// For new records, check the create permission.
			if ($isNew && (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0))
			{
				JToolbarHelper::apply('familyunit.apply');
				JToolbarHelper::save('familyunit.save');
				JToolbarHelper::save2new('familyunit.save2new');
			}

			JToolbarHelper::cancel('familyunit.cancel');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolbarHelper::apply('familyunit.apply');
					JToolbarHelper::save('familyunit.save');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolbarHelper::save2new('familyunit.save2new');
					}
				}
			}

			// If checked out, we can still save
			if ($canDo->get('core.create'))
			{
				JToolbarHelper::save2copy('familyunit.save2copy');
			}

			JToolbarHelper::cancel('familyunit.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
		JToolbarHelper::help('churchdirectory_familyunit', true);
	}

	/**
	 * To set browser title
	 *
	 * @since 1.7.0
	 * @return void
	 */
	protected function setDocument()
	{
		$isNew    = ($this->item->id < 1);
		$document = JFactory::getDocument();
		$document->setTitle(
			$isNew ? JText::_('COM_CHURCHDIRECTORY_FAMILYUNIT_CREATING')
				: JText::sprintf('COM_CHURCHDIRECTORY_FAMILYUNIT_EDITING', $this->item->name)
		);
	}
}
