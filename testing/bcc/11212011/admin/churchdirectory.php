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

// Include dependancies
jimport('joomla.application.component.controller');

$controller	= JController::getInstance('churchdirectory');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();
