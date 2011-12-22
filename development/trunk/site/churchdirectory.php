<?php

/**
 * @version		$Id: contact.php 71 $
 * @package		com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 Joomla Bible Study, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
require_once JPATH_COMPONENT . '/helpers/route.php';

$controller = JController::getInstance('ChurchDirectory');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();
