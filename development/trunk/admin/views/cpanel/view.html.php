<?php

/**
 * Church Direcotry component for Joomla! 1.7
 *
 * @version             $Id: view.html.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class ChurchDirectoryViewCpanel extends JView {

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

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since	1.7.0
     */
    protected function addToolbar() {
        require_once JPATH_COMPONENT . '/helpers/churchdirectory.php';
        $user = JFactory::getUser();

        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_CPANEL'), 'contact.png');

        JToolBarHelper::addNew('contact.add');

        JToolBarHelper::preferences('com_churchdirectory');
        JToolBarHelper::divider();

        JToolBarHelper::help('JHELP_COMPONENTS_CHURCHDIRECTORY_CHURCHDIRECTORY');
    }

}
