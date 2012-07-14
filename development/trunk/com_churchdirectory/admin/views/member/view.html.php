<?php

/**
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * View to edit a contact.
 *
 * @package	ChurchDirectory.Admin
 * @since		1.7.0
 */
class ChurchDirectoryViewMember extends JViewLegacy {

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
        $user = JFactory::getUser();
        $this->groups = $user->groups;

        //@todo need to find a way to do this outside the view.
        $birthdate = $this->form->getValue('birthdate', 'attribs');
        if (!empty($birthdate)):
            $today = getdate();
            $tdate = $today['0'];
            $bdate = strtotime($this->form->getValue('birthdate', 'attribs'));
            $this->age = $this->getAge($bdate, $tdate);
        else:
            $this->age = '0';
        endif;

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        // Set the toolbar
        $this->addToolBar();

        // Display the template
        parent::display($tpl);

        // Set the document
        $this->setDocument();
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
        $canDo = ChurchDirectoryHelper::getActions($this->item->catid, 0);
        JToolBarHelper::title($isNew ? JText::_('COM_CHURCHDIRECTORY_MANAGER_MEMBER_NEW') : JText::_('COM_CHURCHDIRECTORY_MANAGER_MEMBER_EDIT'), 'members');

        // Build the actions for new and existing records.
        if ($isNew) {
            // For new records, check the create permission.
            if ($isNew && (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0)) {
                JToolBarHelper::apply('member.apply');
                JToolBarHelper::save('member.save');
                JToolBarHelper::save2new('member.save2new');
                //JToolBarHelper::custom('member.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
            }
            JToolBarHelper::cancel('member.cancel');
        } else {
            // Can't save the record if it's checked out.
            if (!$checkedOut) {
                // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
                if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
                    JToolBarHelper::apply('member.apply', 'JTOOLBAR_APPLY');
                    JToolBarHelper::save('member.save', 'JTOOLBAR_SAVE');

                    // We can save this record, but check the create permission to see if we can return to make a new one.
                    if ($canDo->get('core.create')) {
                        JToolBarHelper::save2new('member.save2new');
                        //JToolBarHelper::custom('member.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
                    }
                }
            }

            // If checked out, we can still save
            if ($canDo->get('core.create')) {
                JToolBarHelper::save2copy('member.save2copy');
                //JToolBarHelper::custom('member.save2copy', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
            }

            JToolBarHelper::cancel('member.cancel', 'JTOOLBAR_CLOSE');
        }

        JToolBarHelper::divider();
        JToolBarHelper::help('member_contact', TRUE);
    }

    protected function setDocument() {
        $isNew = ($this->item->id < 1);
        $document = JFactory::getDocument();
        $document->setTitle($isNew ? JText::_('COM_CHURCHDIRECTORY_CHURCHDIRECTORY_MEMBER_CREATING') : JText::_('COM_CHURCHDIRECTORY_CHURCHDIRECTORY_MEMBER_EDITING'));
        $document->addScript(JURI::root() . "media/com_churchdirectory/js/churchdirectory.js");
    }

    /**
     * @todo need to move this outside the view.
     * Get the age of a person in years at a given time
     *
     * @param       int     $dob    Date Of Birth
     * @param       int     $tdate  The Target Date
     * @return      int     The number of years
     *
     */
    protected function getAge($bdate, $tdate) {
        $age = 0;
        while ($tdate > $bdate = strtotime('+1 year', $bdate)) {
            ++$age;
        }
        return $age;
    }

}
