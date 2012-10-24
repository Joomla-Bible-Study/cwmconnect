<?php

/**
 * View FaimlyUnits
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;


jimport('joomla.application.component.helper');
jimport('joomla.i18n.help');

/**
 * View class for a list of churchdirectories.
 *
 * @package ChurchDirectory.Admin
 * @since   1.7.0
 */
class ChurchDirectoryViewFamilyUnits extends JViewLegacy {

    /**
     * Protect items
     * @var array
     */
    protected $items;

    /**
     * Protect pagination
     * @var array
     */
    protected $pagination;

    /**
     * Protect state
     * @var array
     */
    protected $state;

    /**
     * Display the view
     * @param string $tpl
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
        JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_FAMILYUNITS'), 'churchdirectory');

        if ($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_churchdirectory', 'core.create'))) > 0) {
            JToolBarHelper::addNew('familyunit.add');
        }

        if (($canDo->get('core.edit')) || ($canDo->get('core.edit.own'))) {
            JToolBarHelper::editList('familyunit.edit');
        }

        if ($canDo->get('core.edit.state')) {
            JToolBarHelper::divider();
            JToolBarHelper::publish('familyunits.publish', 'JTOOLBAR_PUBLISH', true);
            JToolBarHelper::unpublish('familyunits.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            JToolBarHelper::divider();
            JToolBarHelper::checkin('familyunits.checkin');
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            JToolBarHelper::deleteList('', 'familyunits.delete', 'JTOOLBAR_EMPTY_TRASH');
            JToolBarHelper::divider();
        } elseif ($canDo->get('core.edit.state')) {
            JToolBarHelper::trash('familyunits.trash');
            JToolBarHelper::divider();
        }

        if ($canDo->get('core.admin')) {
            JToolBarHelper::preferences('com_churchdirectory');
            JToolBarHelper::divider();
        }

        JToolBarHelper::help('churchdirectory_familyunit', TRUE);
    }

    /**
     * Set browser title
     * @since 1.7.0
     */
    protected function setDocument() {
        $document = JFactory::getDocument();
        $document->setTitle(JText::_('COM_CHURCHDIRECTORY_FAMILYUNITS'));
    }

}
