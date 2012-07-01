<?php

/**
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

class ChurchDirectoryControllerDirHeaders extends JControllerAdmin {

    /**
     * Constructor.
     *
     * @param	array	$config	An optional associative array of configuration settings.
     *
     * @return	ChurchDirectoryControllerContacts
     * @see		JController
     * @since	1.7.0
     */
    public function __construct($config = array()) {
        parent::__construct($config);
    }

    /**
     * Method to toggle the featured setting of a list of contacts.
     *
     * @return	void
     * @since	1.7.0
     */
    function featured() {
        // Check for request forgeries
        JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Initialise variables.
        $user = JFactory::getUser();
        $ids = JRequest::getVar('id', array(), '', 'array');
        $task = $this->getTask();
        $value = JArrayHelper::getValue($values, $task, 0, 'int');
        // Get the model.
        $model = $this->getModel();

        // Access checks.
        foreach ($ids as $i => $id) {
            $item = $model->getItem($id);
            if (!$user->authorise('core.edit.state')) {
                // Prune items that you can't change.
                unset($ids[$i]);
                JError::raiseNotice(403, JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
            }
        }

        if (empty($ids)) {
            JError::raiseWarning(500, JText::_('COM_CHURCHDIRECTORY_NO_ITEM_SELECTED'));
        } else {
            // Publish the items.
            if (!$model->featured($ids, $value)) {
                JError::raiseWarning(500, $model->getError());
            }
        }

        $this->setRedirect('index.php?option=com_churchdirectory&view=dirheaders');
    }

    /**
     * Proxy for getModel.
     *
     * @param	string	$name	The name of the model.
     * @param	string	$prefix	The prefix for the PHP class name.
     *
     * @return	JModel
     * @since	1.7.0
     */
    public function getModel($name = 'DirHeader', $prefix = 'ChurchDirectoryModel', $config = array('ignore_request' => true)) {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }

}
