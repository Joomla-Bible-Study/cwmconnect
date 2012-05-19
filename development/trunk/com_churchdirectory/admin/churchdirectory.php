<?php

/**
 * @version		$Id: churchdirectory.php 71 $
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

// Set some global property
addCSS();

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_churchdirectory')) {
    return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Require helper file
JLoader::register('ChurchDirectoryHelper', dirname(__FILE__) . DS . 'helpers' . DS . 'churchdirectory.php');

// Get an instance of the controller prefixed by ChurchDirectory
$controller = JController::getInstance('churchdirectory');

// Perform the Request task
$controller->execute(JRequest::getCmd('task'));

// Redirect if set by the controller
$controller->redirect();

/**
 * Global css
 *
 * @since   1.7.0
 */
function addCSS() {
    JHTML::stylesheet('general.css', 'media/com_churchdirectory/css/');
    JHTML::stylesheet('icons.css', 'media/com_churchdirectory/css/');
}