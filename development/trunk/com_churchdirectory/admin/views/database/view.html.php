<?php

/**
 * ChurchDirectory View
 * @package	ChurchDirectory.Admin
 * @copyright	(C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license	GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * View to fix Database.
 *
 * @package ChurchDirectory.Admin
 * @since   1.7.0
 */
class ChurchDirectoryViewDatabase extends JViewLegacy {

    /**
     * Display the view
     * @param string $tpl
     * @since 1.7.0
     */
    public function display($tpl = null) {
        $language = JFactory::getLanguage();
        $language->load('com_installer');

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
        $this->jversion = $this->get('CompVersion');
        //end for database

        $errors = count($this->errors);
        if (!(strncmp($this->schemaVersion, $this->jversion, 5) === 0)) {
            $this->errorCount++;
        }
        if (!$this->filterParams) {
            $this->errorCount++;
        }
        if (($this->updateVersion != $this->jversion)) {
            $this->errorCount++;
        }
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
        $canDo = ChurchDirectoryHelper::getActions();

        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_DATABASE'), 'churchdirectory');

        JToolBarHelper::custom('database.cancel', 'back', 'back', 'JTOOLBAR_BACK', false, false);
        JToolBarHelper::divider();
        JToolBarHelper::custom('database.fix', 'refresh', 'refresh', 'COM_CHURCHDIRECTORY_DATABASE_FIX', false, false);
    }

    /**
     * Set document browser title
     * @since 1.7.0
     */
    protected function setDocument() {
        $document = JFactory::getDocument();
        $document->setTitle(JText::_('COM_CHURCHDIRECTORY_DATABASE'));
    }

}
