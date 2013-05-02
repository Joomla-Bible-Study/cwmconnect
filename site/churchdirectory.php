<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT . '/helpers/route.php';

JLoader::register('RenderHelper', JPATH_SITE . '/components/com_churchdirectory/helpers/render.php');

if (!version_compare(JVERSION, '3.0', 'ge'))
{
	JHtml::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_churchdirectory/helpers/html');
	JHtml::_('bootstrap.framework');
	JHtml::_('bootstrap.loadcss');
	JHtml::stylesheet('media/com_churchdirectory/css/bootstrap-j2.5.css');
}

JHTML::stylesheet('media/com_churchdirectory/css/general.css');
JHTML::stylesheet('media/com_churchdirectory/css/churchdirectory.css');

$controller = JControllerLegacy::getInstance('ChurchDirectory');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
