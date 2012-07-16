<?php

/**
 * Church Direcotry component for Joomla! 1.7
 *
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once (JPATH_COMPONENT_ADMINISTRATOR . '/liveupdate/liveupdate.php');

/**
 * @package ChurchDirectory.Admin
 * @since 1.7.0
 */
class ChurchDirectoryViewCpanel extends JViewLegacy {

    protected $items;
    protected $pagination;
    protected $state;

    public function display($tpl = null) {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        jimport('joomla.version');
        $version = new JVersion();
        
        if ($version->RELEASE == '3.0') {
            $this->versionName = TRUE;
        } else {
            $this->versionName = FALSE;
        }

        // Set the toolbar
        $this->addToolbar();

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
        $canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));
        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_CPANEL'), 'churchdirectory.png');

        if ($canDo->get('core.admin')) {
            JToolBarHelper::divider();
            JToolBarHelper::preferences('com_churchdirectory');
            JToolBarHelper::divider();
        }

        JToolBarHelper::help('churchdirectory', TRUE);
    }

    /**
     * Set browser title
     * @since 1.7.0
     */
    protected function setDocument() {
        $document = JFactory::getDocument();
        $document->setTitle(JText::_('COM_CHURCHDIRECTORY_ADMINISTRATION'));
    }

}
