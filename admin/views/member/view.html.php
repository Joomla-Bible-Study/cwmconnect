<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * View to edit a contact.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryViewMember extends JViewLegacy
{

	/**
	 * Protect form
	 *
	 * @var array
	 */
	protected $form;

	/**
	 * Protect items
	 *
	 * @var object
	 */
	protected $item;

	/**
	 * Protect state
	 *
	 * @var object
	 */
	protected $state;

	/** @type  Object */
	protected $canDo;

	protected $groups;

	protected $age;

	protected $access;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display ($tpl = null)
	{
		// Initialise variables.
		$this->form   = $this->get('Form');
		$this->item   = $this->get('Item');
		$this->state  = $this->get('State');
		$user         = JFactory::getUser();
		$this->groups = $user->groups;

		/* Get Age of Member */
		$this->age = ChurchDirectoryHelper::getAge($this->form->getValue('birthdate'));

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JFactory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}
		$itemacess = $this->state->params->get('protectedaccess');
		$groups    = $this->groups;

		if (isset($groups[$itemacess]) || isset($groups['8']))
		{
			$this->access = true;
		}
		else
		{
			$this->access = false;
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
	protected function addToolbar ()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$user       = JFactory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$canDo      = ChurchDirectoryHelper::getActions('com_churchdirectory', 'category', $this->item->catid);
		JToolbarHelper::title(
			$isNew ? JText::_('COM_CHURCHDIRECTORY_MANAGER_MEMBER_NEW')
				: JText::_('COM_CHURCHDIRECTORY_MANAGER_MEMBER_EDIT'), 'churchdirectory');

		// Build the actions for new and existing records.
		if ($isNew)
		{
			// For new records, check the create permission.
			if ($isNew && (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0))
			{
				JToolbarHelper::apply('member.apply');
				JToolbarHelper::save('member.save');
				JToolbarHelper::save2new('member.save2new');
			}
			JToolbarHelper::cancel('member.cancel');
		}
		else
		{
			// Can't save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolbarHelper::apply('member.apply', 'JTOOLBAR_APPLY');
					JToolbarHelper::save('member.save', 'JTOOLBAR_SAVE');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolbarHelper::save2new('member.save2new');
					}
				}
			}

			// If checked out, we can still save
			if ($canDo->get('core.create'))
			{
				JToolbarHelper::save2copy('member.save2copy');
			}

			JToolbarHelper::cancel('member.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
		JToolbarHelper::help('member_contact', true);
	}

	/**
	 * Set Document title
	 *
	 * @return void
	 */
	protected function setDocument ()
	{
		$isNew    = ($this->item->id < 1);
		$document = JFactory::getDocument();
		$document->setTitle(
			$isNew ? JText::_('COM_CHURCHDIRECTORY_CHURCHDIRECTORY_MEMBER_CREATING')
				: JText::_('COM_CHURCHDIRECTORY_CHURCHDIRECTORY_MEMBER_EDITING')
		);
	}

}
