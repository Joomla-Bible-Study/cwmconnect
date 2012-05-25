<?php

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
require_once JPATH_COMPONENT . '/helpers/route.php';

$controller = JController::getInstance('ChurchDirectory');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();
