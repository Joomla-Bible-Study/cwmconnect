<?php
/**
 * Core Admin Church Directory file
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2015 (C) Joomla Bible Study Team All rights reserved
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.christianwebministries.org
 * */
// No Direct Access
defined('_JEXEC') or die;

if (defined('CHURCH_DIRECTORY_LOADED'))
{
	return;
}

// Manually enable code profiling by setting value to 1
define('CHURCH_DIRECTORY_PROFILER', 0);

// Load JBSM Class
JLoader::discover('ChurchDirectory', JPATH_ROOT . '/components/com_churchdirectory/helpers', 'false', 'true');
JLoader::discover('ChurchDirectoryTable', JPATH_ROOT . '/components/com_churchdirectory/tables', 'false', 'true');
JLoader::discover('ChurchDirectory', JPATH_ADMINISTRATOR . '/components/com_churchdirectory/helpers', 'false', 'true');
JLoader::discover('ChurchDirectoryTable', JPATH_ADMINISTRATOR . '/components/com_churchdirectory/tables', 'false', 'true');
JLoader::register('ChurchDirectoryHelper', JPATH_ADMINISTRATOR . '/components/com_churchdirectory/helpers/churchdirectory.php');
JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_churchdirectory/helpers/html/');

JHtml::stylesheet('media/com_churchdirectory/css/general.css');

// If phrase is not found in specific language file, load english language file:
$language = JFactory::getLanguage();
$language->load('com_churchdirectory', JPATH_ADMINISTRATOR . '/components/com_churchdirectory', 'en-GB', true);
$language->load('com_churchdirectory', JPATH_ADMINISTRATOR . '/components/com_churchdirectory', null, true);

// Include the JLog class.
jimport('joomla.log.log');
JLog::addLogger(
	array(
		'text_file' => 'com_churchdirectory.errors.php'
	),
	JLog::ALL,
	'com_churchdirectory'
);

// ChurchDirectory has been initialized
define('CHURCH_DIRECTORY_LOADED', 1);
