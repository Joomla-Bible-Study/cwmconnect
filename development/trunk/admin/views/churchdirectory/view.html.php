<?php

/**
 * @version             $Id: view.html.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
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
class ChurchDirectoryViewChurchDirectory extends JView {

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
        $this->canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));
        //var_dump($this->item);
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
        $canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'), $this->item->id);
        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_CONTACT'), 'churchdirectory');

        // Build the actions for new and existing records.
        if ($isNew) {
            // For new records, check the create permission.
            if ($isNew && (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0)) {
                JToolBarHelper::apply('churchdirectory.apply');
                JToolBarHelper::save('churchdirectory.save');
                JToolBarHelper::save2new('churchdirectory.save2new');
                JToolBarHelper::cancel('churchdirectory.cancel');
            }
        } else {
            // Can't save the record if it's checked out.
            if (!$checkedOut) {
                // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
                if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
                    JToolBarHelper::apply('churchdirectory.apply');
                    JToolBarHelper::save('churchdirectory.save');

                    // We can save this record, but check the create permission to see if we can return to make a new one.
                    if ($canDo->get('core.create')) {
                        JToolBarHelper::save2new('churchdirectory.save2new');
                    }
                }
            }

            // If checked out, we can still save
            if ($canDo->get('core.create')) {
                JToolBarHelper::save2copy('churchdirectory.save2copy');
            }

            JToolBarHelper::cancel('churchdirectory.cancel', 'JTOOLBAR_CLOSE');
        }

        JToolBarHelper::divider();
        JToolBarHelper::help('churchdirectory_contact', TRUE);
    }

}
