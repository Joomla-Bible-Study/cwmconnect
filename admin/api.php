<?php
/**
 * Core Admin Church Directory file
 *
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2015 (C) Joomla Bible Study Team All rights reserved
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.JoomlaBibleStudy.org
 * */
// No Direct Access
defined('_JEXEC') or die;

if (defined('CHURCH_DIRECTORY_LOADED'))
{
	return;
}

// Manually enable code profiling by setting value to 1
define('JBSM_PROFILER', 0);

// Load JBSM Class
JLoader::discover('ChurchDirectory', JPATH_ROOT . 'components/com_churchdirectory', 'true', 'true');
JLoader::discover('ChurchDirectory', JPATH_ADMINISTRATOR . 'components/com_churchdirectory', 'true', 'true');
JHtml::addIncludePath(JPATH_ADMINISTRATOR . 'components/com_churchdirectory/helpers' . '/html/');

// JBSM has been initialized
define('CHURCH_DIRECTORY', 1);
