<?php

/**
 * @version		$Id: controller.php 71 $
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_churchdirectory')) {
    return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

jimport('joomla.application.component.controller');

/**
 * Component Controller
 *
 * @package		Joomla.Administrator
 * @subpackage	com_churchdirectory
 */
class ChurchDirectoryController extends JController {

    /**
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
        if ($view == 'churchdirectory' && $layout == 'edit' && !$this->checkEditId('com_churchdirectory.edit.churchdirectory', $id)) {

            // Somehow the person just went to the form - we don't allow that.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
            $this->setMessage($this->getError(), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=churchdirectories', false));

            return false;
        }

        parent::display();

        return $this;
    }

}
