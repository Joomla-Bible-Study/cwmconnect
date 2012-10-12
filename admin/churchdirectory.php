<?php

/**
 * Main Admin start file
 *
 * @package		ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

// Set some global property
addCSS();

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_churchdirectory')) {
    return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}


require_once(JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'liveupdate' . DIRECTORY_SEPARATOR . 'liveupdate.php');
if (JRequest::getCmd('view', '') == 'liveupdate') {
    LiveUpdate::handleRequest();
    return;
}

// Require helper file
JLoader::register('ChurchDirectoryHelper', dirname(__FILE__) . '/helpers/churchdirectory.php');

// Get an instance of the controller prefixed by ChurchDirectory
$controller = JControllerLegacy::getInstance('Churchdirectory');

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
    JHTML::stylesheet('media/com_churchdirectory/css/general.css');
    JHTML::stylesheet('media/com_churchdirectory/css/icons.css');
}