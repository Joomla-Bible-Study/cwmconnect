<?php

/**
 * @version		$Id: view.html.php 1.7.0 $
 * @package	com_churchdirectory
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit a contact.
 *
 * @package	com_churchdirectory
 * @since		1.7.0
 */
class ChurchDirectoryViewFamilyUnit extends JView {

    protected $form;
    protected $item;
    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        // Initialiase variables.
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        $this->members = $this->get('members');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since	1.7.0
     */
    protected function addToolbar() {
        JRequest::setVar('hidemainmenu', true);
        $user = JFactory::getUser();
        $userId = $user->get('id');
        $isNew = ($this->item->id == 0);
        $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
        $canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));

        JToolBarHelper::title($isNew ? JText::_('COM_CHURCHDIRECTORY_MANAGER_FAMILYUNIT_NEW') : JText::_('COM_CHURCHDIRECTORY_MANAGER_FAMILYUNIT_EDIT'), 'churchdirectory');

        // Build the actions for new and existing records.
        if ($isNew) {
            // For new records, check the create permission.
            if ($isNew && (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0)) {
                JToolBarHelper::apply('familyunit.apply');
                JToolBarHelper::save('familyunit.save');
                JToolBarHelper::save2new('familyunit.save2new');
            }

            JToolBarHelper::cancel('familyunit.cancel');
        } else {
            // Can't save the record if it's checked out.
            if (!$checkedOut) {
                // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
                if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
                    JToolBarHelper::apply('familyunit.apply');
                    JToolBarHelper::save('familyunit.save');

                    // We can save this record, but check the create permission to see if we can return to make a new one.
                    if ($canDo->get('core.create')) {
                        JToolBarHelper::save2new('familyunit.save2new');
                    }
                }
            }

            // If checked out, we can still save
            if ($canDo->get('core.create')) {
                JToolBarHelper::save2copy('familyunit.save2copy');
            }

            JToolBarHelper::cancel('familyunit.cancel', 'JTOOLBAR_CLOSE');
        }

        JToolBarHelper::divider();
        JToolBarHelper::help('churchdirectory_familyunit', TRUE);
    }

    protected function setDocument() {
        $isNew = ($this->item->id < 1);
        $document = JFactory::getDocument();
        $document->setTitle($isNew ? JText::_('COM_CHURCHDIRECTORY_FAMILYUNIT_CREATING') : JText::_('COM_CHURCHDIRECTORY_FAMILYUNIT_EDITING'));
    }

}
