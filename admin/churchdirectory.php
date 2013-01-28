<?php
/**
 * @package    ChurchDirectory.Admin
 *
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Set some global property
addCSS();

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_churchdirectory'))
{
	JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');

	return false;
}

require_once JPATH_COMPONENT_ADMINISTRATOR . '/liveupdate/liveupdate.php';

if (JFactory::getApplication()->input->get('view', '') == 'liveupdate')
{
	LiveUpdate::handleRequest();

	return;
}

// Require helper file
// Register all files in the /the/path/ folder as classes with a name like:
// Prefix<Filename>
JLoader::discover('j', '/the/path/');
JLoader::register('ChurchDirectoryHelper', dirname(__FILE__) . '/helpers/churchdirectory.php');


$controller = JControllerLegacy::getInstance('Churchdirectory');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

/**
 * Global css
 *
 * @since   1.7.0
 */
function addCSS()
{
	//JHTML::stylesheet('media/com_churchdirectory/jui/css/bootstrap.min.css');
	//JHTML::stylesheet('media/com_churchdirectory/css/icons.css');
}
