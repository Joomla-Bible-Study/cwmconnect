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
	 * @since    1.7.0
	 */
	protected $form;

	/**
	 * Protect items
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

	/**
	 * @type  Object
	 * @since    1.7.0
	 */
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
	 *
	 * @since    1.7.0
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
			throw new Exception(implode("\n", $errors), 500);
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

		// If we are forcing a language in modal (used for associations).
		if ($this->getLayout() === 'modal' && $forcedLanguage = JFactory::getApplication()->input->get('forcedLanguage', '', 'cmd'))
		{
			// Set the language field to the forcedLanguage and disable changing it.
			$this->form->setValue('language', null, $forcedLanguage);
			$this->form->setFieldAttribute('language', 'readonly', 'true');

			// Only allow to select categories with All language or with the forced language.
			$this->form->setFieldAttribute('catid', 'language', '*,' . $forcedLanguage);

			// Only allow to select tags with All language or with the forced language.
			$this->form->setFieldAttribute('tags', 'language', '*,' . $forcedLanguage);
		}

		// Set the toolbar
		$this->addToolbar();

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
				: JText::_('COM_CHURCHDIRECTORY_MANAGER_MEMBER_EDIT'), 'address contact');

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
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			$itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

			// Can't save the record if it's checked out.
			if (!$checkedOut && $itemEditable)
			{
				JToolbarHelper::apply('member.apply');
				JToolbarHelper::save('member.save');

				// We can save this record, but check the create permission to see if we can return to make a new one.
				if ($canDo->get('core.create'))
				{
					JToolbarHelper::save2new('member.save2new');
				}
			}

			// If checked out, we can still save
			if ($canDo->get('core.create'))
			{
				JToolbarHelper::save2copy('member.save2copy');
			}

			if (JComponentHelper::isEnabled('com_contenthistory')
				&& $this->state->params->get('save_history', 0)
				&& $itemEditable)
			{
				JToolbarHelper::versions('com_churchdircetoyr.member', $this->item->id);
			}

			JToolbarHelper::cancel('member.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolbarHelper::divider();
		JToolbarHelper::help('member_contact', true);
	}
}
