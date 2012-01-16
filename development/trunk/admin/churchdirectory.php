<?php

/**
 * @version		$Id: churchdirectory.php 71 $
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_churchdirectory')) {
    return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

addCSS();

// Include dependancies
jimport('joomla.application.component.controller');

$controller = JController::getInstance('churchdirectory');
$controller->execute(JRequest::getCmd('task'));
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