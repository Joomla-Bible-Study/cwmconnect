<?php
/**
 * @version		$Id: churchdirectory.php $
 * @package		com_churchdirectory
 * @copyright	Copyright (C) 2005 - 2011 Nasvhille First SDA Church, Inc. All rights reserved.
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

$controller	= JController::getInstance('churchdirectory');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();

/**
 * Global css
 *
 * @since   1.7.0
 */
function addCSS() {
	$doc = & JFactory::getDocument();
	//$doc->addStyleSheet(JURI::base() . 'components/com_churchdirectory/css/general.css');
	$doc->addStyleSheet(JURI::base() . 'components/com_churchdirectory/css/icons.css');
}