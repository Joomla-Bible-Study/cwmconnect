<?php

/**
 * @version		$Id: churchdirectories.php 71 $
 * @package             com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Articles list controller class.
 *
 * @package	com_churchdirectory
 * @since	1.7.0
 */
class ChurchDirectoryControllerChurchDirectories extends JControllerAdmin {

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

        $this->registerTask('unfeatured', 'featured');
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
        $ids = JRequest::getVar('cid', array(), '', 'array');
        $values = array('featured' => 1, 'unfeatured' => 0);
        $task = $this->getTask();
        $value = JArrayHelper::getValue($values, $task, 0, 'int');
        // Get the model.
        $model = $this->getModel();

        // Access checks.
        foreach ($ids as $i => $id) {
            $item = $model->getItem($id);
            if (!$user->authorise('core.edit.state', 'com_churchdirectory.category.' . (int) $item->catid)) {
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

        $this->setRedirect('index.php?option=com_churchdirectory&view=churchdirectories');
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
    public function getModel($name = 'ChurchDirectory', $prefix = 'ChurchDirectoryModel', $config = array('ignore_request' => true)) {
        $model = parent::getModel($name, $prefix, $config);

        return $model;
    }

}
