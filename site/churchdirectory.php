<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Check for PHP4
if (defined('PHP_VERSION'))
{
	$version = PHP_VERSION;
}
elseif (function_exists('phpversion'))
{
	$version = phpversion();
}
else
{
// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}

// Old PHP version detected. EJECT! EJECT! EJECT!
if (!version_compare($version, '5.3.0', '>='))
{
	JFactory::getApplication()->enqueueMessage('PHP versions 4.x, 5.0, 5.1 and 5.2 are no longer supported by Church Direcotory.<br/><br/>The version of PHP used on your site is obsolete and contains known security vulenrabilities. Moreover, it is missing features required by Church Directory to work properly or at all. Please ask your host to upgrade your server to the latest PHP 5.3 release. Thank you!', 'error');

	return false;
}

require_once JPATH_COMPONENT . '/helpers/route.php';

JLoader::register('RenderHelper', JPATH_SITE . '/components/com_churchdirectory/helpers/render.php');
JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_churchdirectory/helpers/html');
JHtml::_('bootstrap.framework');
JHtml::_('bootstrap.loadcss');

if (!version_compare(JVERSION, '3.0', 'ge'))
{
	JHtml::stylesheet('media/com_churchdirectory/css/bootstrap-j2.5.css');
}

// Load FOF
include_once JPATH_SITE . '/libraries/fof/include.php';

// Load tcpdf
include_once JPATH_SITE . '/libraries/tcpdf/tcpdf.php';

JHTML::stylesheet('media/com_churchdirectory/css/general.css');
JHTML::stylesheet('media/com_churchdirectory/css/churchdirectory.css');

$controller = JControllerLegacy::getInstance('ChurchDirectory');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
