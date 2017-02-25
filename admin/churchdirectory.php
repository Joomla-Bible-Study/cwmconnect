<?php
/**
 * @package    ChurchDirectory.Admin
 *
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Always load JBSM API if it exists.
$api = JPATH_ADMINISTRATOR . '/components/com_churchdirectory/api.php';

if (file_exists($api))
{
	require_once $api;
}

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_churchdirectory'))
{
	JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');

	return false;
}

$controller = JControllerLegacy::getInstance('churchdirectory');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
