<?php

/**
 * @version		$Id: view.html.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
jimport('joomla.application.component.helper');
jimport('joomla.i18n.help');

/**
 * View class for a list of churchdirectories.
 *
 * @package	com_churchdirectory
 * @since		1.7.0
 */
class ChurchDirectoryViewChurchDirectories extends JView {

    protected $items;
    protected $pagination;
    protected $state;

    /**
     * Display the view
     *
     * @return	void
     */
    public function display($tpl = null) {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        // Preprocess the list of items to find ordering divisions.
        // TODO: Complete the ordering stuff with nested sets
        foreach ($this->items as &$item) {
            $item->order_up = true;
            $item->order_dn = true;
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
        $canDo = ChurchDirectoryHelper::getActions($this->state->get('filter.category_id'));
        $user = JFactory::getUser();
        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_CONTACTS'), 'churchdirectory.png');

        if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create'))) > 0) {
            JToolBarHelper::addNew('churchdirectory.add');
        }

        if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own'))) {
            JToolBarHelper::editList('churchdirectory.edit');
        }

        if ($canDo->get('core.edit.state')) {
            JToolBarHelper::divider();
            JToolBarHelper::publish('churchdirectories.publish', 'JTOOLBAR_PUBLISH', true);
            JToolBarHelper::unpublish('churchdirectories.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            JToolBarHelper::divider();
            JToolBarHelper::archiveList('churchdirectories.archive');
            JToolBarHelper::checkin('churchdirectories.checkin');
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            JToolBarHelper::deleteList('', 'churchdirectories.delete', 'JTOOLBAR_EMPTY_TRASH');
            JToolBarHelper::divider();
        } elseif ($canDo->get('core.edit.state')) {
            JToolBarHelper::trash('churchdirectories.trash');
            JToolBarHelper::divider();
        }

        if ($canDo->get('core.admin')) {
            JToolBarHelper::preferences('com_churchdirectory');
            JToolBarHelper::divider();
        }

        JToolBarHelper::help('churchdirectory_contacts', TRUE);
    }

}
