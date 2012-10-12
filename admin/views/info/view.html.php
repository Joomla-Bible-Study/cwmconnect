<?php

/**
 * View for Info
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * Class for Info
 * @package ChurchDirectory.Admin
 * @since 1.7.0
 */
class ChurchDirectoryViewInfo extends JViewLegacy {

    /**
     * Protect Items
     * @var array
     */
    protected $items;

    /**
     * Protect Pagination
     * @var array
     */
    protected $pagination;

    /**
     * Protect State
     * @var array
     */
    protected $state;

    /**
     * Display function
     * @param  string $tpl
     * @return boolean
     * @since 1.7.0
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
        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_INFO'), 'churchdirectory');


        JToolBarHelper::help('churchdirectory', TRUE);
    }

}
