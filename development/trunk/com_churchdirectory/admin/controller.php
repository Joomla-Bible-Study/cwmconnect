<?php

/**
 * ChurchDirectory Controller
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

/**
 * Component Controller
 *
 * @package	ChurchDirectory.Admin
 */
class ChurchDirectoryController extends JControllerLegacy {

    /**
     * The Default View
     * @var		string	The default view.
     * @since	1.7.0
     */
    protected $default_view = 'cpanel';

    /**
     * Method to display a view.
     *
     * @param	boolean			If true, the view output will be cached
     * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.7.0
     */
    public function display($cachable = false, $urlparams = false) {

        require_once JPATH_COMPONENT . '/helpers/churchdirectory.php';

        // Load the submenu.
        ChurchDirectoryHelper::addSubmenu(JRequest::getCmd('view', 'cpanel'));

        $view = JRequest::getCmd('view', 'cpanel');
        $layout = JRequest::getCmd('layout', 'default');
        $id = JRequest::getInt('id');

        $type = JRequest::getWord('view');
        if (!$type) {
            JRequest::setVar('view', 'cpanel');
        }
        // Check for edit form.
        if ($view == 'member' && $layout == 'edit' && !$this->checkEditId('com_churchdirectory.edit.member', $id)) {

            // Somehow the person just went to the form - we don't allow that.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
            $this->setMessage($this->getError(), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=members', false));

            return false;
        }

        // Check for edit form.
        if ($view == 'kml' && $layout == 'edit' && !$this->checkEditId('com_churchdirectory.edit.kml', $id)) {

            // Somehow the person just went to the form - we don't allow that.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
            $this->setMessage($this->getError(), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=cpanel', false));

            return false;
        }

        // Check for edit form.
        if ($view == 'familyunit' && $layout == 'edit' && !$this->checkEditId('com_churchdirectory.edit.familyunit', $id)) {

            // Somehow the person just went to the form - we don't allow that.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
            $this->setMessage($this->getError(), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=familyunits', false));

            return false;
        }

        parent::display();

        return $this;
    }

}
