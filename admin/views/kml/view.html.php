<?php

/**
 * View KML
 * @package	ChurchDirectory.Admin
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;



/**
 * View to edit a contact.
 *
 * @package	ChurchDirectory.Admin
 * @since		1.7.0
 */
class ChurchDirectoryViewKML extends JViewLegacy {

    /**
     * Protect form
     * @var array
     */
    protected $form;

    /**
     * Protect item
     * @var array
     */
    protected $item;

    /**
     * Protect state
     * @var array
     */
    protected $state;

    /**
     * Display the view
     * @param string $tpl
     */
    public function display($tpl = null) {
        // Initialiase variables.
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

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
        $canDo = ChurchDirectoryHelper::getActions(0);

        JToolBarHelper::title($isNew ? JText::_('COM_CHURCHDIRECTORY_MANAGER_KML_NEW') : JText::_('COM_CHURCHDIRECTORY_MANAGER_KML_EDIT'), 'kml');

        // Build the actions for new and existing records.
        if ($isNew) {
            // For new records, check the create permission.
            if ($isNew && (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create')) > 0)) {
                JToolBarHelper::apply('kml.apply');
                JToolBarHelper::save('kml.save');
                JToolBarHelper::save2new('kml.save2new');
            }

            JToolBarHelper::cancel('kml.cancel');
        } else {
            // Can't save the record if it's checked out.
            if (!$checkedOut) {
                // Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
                if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
                    JToolBarHelper::apply('kml.apply');
                    JToolBarHelper::save('kml.save');

                    // We can save this record, but check the create permission to see if we can return to make a new one.
                    if ($canDo->get('core.create')) {
                        JToolBarHelper::save2new('kml.save2new');
                    }
                }
            }

            // If checked out, we can still save
            if ($canDo->get('core.create')) {
                JToolBarHelper::save2copy('kml.save2copy');
            }

            JToolBarHelper::cancel('kml.cancel', 'JTOOLBAR_CLOSE');
        }

        JToolBarHelper::divider();
        JToolBarHelper::help('churchdirectory_kml', TRUE);
    }

    /**
     * Set browser title
     * @since 1.7.0
     */
    protected function setDocument() {
        $isNew = ($this->item->id < 1);
        $document = JFactory::getDocument();
        $document->setTitle($isNew ? JText::_('COM_CHURCHDIRECTORY_KML_CREATING') : JText::sprintf('COM_CHURCHDIRECTORY_KML_EDITING', $this->item->name));
    }

}
