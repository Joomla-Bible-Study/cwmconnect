<?php
/**
 * @package    ChurchDirectory.Admin
 *
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_churchdirectory'))
{
	JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'warning');

	return false;
}

// Set some global property
addCSS();

$controller = JControllerLegacy::getInstance('churchdirectory');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

/**
 * Global css
 *
 * @since   1.7.0
 *
 * @return void
 */
function addCSS()
{
	JHtml::stylesheet('media/com_churchdirectory/css/general.css');
}
