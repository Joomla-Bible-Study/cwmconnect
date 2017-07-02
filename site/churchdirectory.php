<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.framework');
JHtml::_('bootstrap.loadcss');

// Always load JBSM API if it exists.
$api = JPATH_ADMINISTRATOR . '/components/com_churchdirectory/api.php';

if (file_exists($api))
{
	require_once $api;
}

JLoader::register('ChurchDirectoryHelperRoute', JPATH_COMPONENT . '/helpers/route.php');

JHtml::stylesheet('media/com_churchdirectory/css/churchdirectory.css');
$controller = JControllerLegacy::getInstance('ChurchDirectory');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
