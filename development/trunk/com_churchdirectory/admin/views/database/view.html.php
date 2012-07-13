<?php

/**
 * @package	ChurchDirectory.Admin
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit a contact.
 *
 * @package ChurchDirectory.Admin
 * @since   1.7.0
 */
class ChurchDirectoryViewDatabase extends JView {

    protected $form;
    protected $item;
    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        $language = JFactory::getLanguage();
        $language->load('com_installer');

        // Get data from the model
        $this->form = $this->get("Form");
        $this->item = $this->get("Item");
        $this->state = $this->get("State");

        // Get data from the model for database
        $this->changeSet = $this->get('Items');
        $this->errors = $this->changeSet->check();
        $this->results = $this->changeSet->getStatus();
        $this->schemaVersion = $this->get('SchemaVersion');
        $this->updateVersion = $this->get('UpdateVersion');
        $this->filterParams = $this->get('DefaultTextFilters');
        $this->schemaVersion = ($this->schemaVersion) ? $this->schemaVersion : JText::_('JNONE');
        $this->updateVersion = ($this->updateVersion) ? $this->updateVersion : JText::_('JNONE');
        $this->pagination = $this->get('Pagination');
        $this->errorCount = count($this->errors);
        //end for database

        $this->setLayout('form');

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
        $canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));

        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_DATABASE'), 'churchdirectory');

        JToolBarHelper::custom( 'database.cancel', 'back', 'back', 'JTOOLBAR_BACK', false, false );
        JToolBarHelper::divider();
        JToolBarHelper::custom('database.fix', 'refresh', 'refresh', 'COM_CHURCHDIRECTORY_DATABASE_FIX', false, false);
    }

    /**
     * Set document browser title
     * @since 1.7.0
     */
    protected function setDocument() {
        $isNew = ($this->item->id < 1);
        $document = JFactory::getDocument();
        $document->setTitle($isNew ? JText::_('COM_CHURCHDIRECTORY_DIRHEADER_CREATING') : JText::sprintf('COM_CHURCHDIRECTORY_DIRHEADER_EDITING', $this->item->name));
    }

}
