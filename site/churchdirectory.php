<?php

/**
 * Site main component file
 * @package		ChurchDirectory.Site
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/helpers/route.php';

JHTML::stylesheet('general.css', 'media/com_churchdirectory/css/');
JHTML::stylesheet('churchdirectory.css', 'media/com_churchdirectory/css/');

$controller = JControllerLegacy::getInstance('ChurchDirectory');
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();
