<?php
/**
 * Model for Birthday and Anniversary
 *
 * @package     ChurchDirectory
 * @subpackage  Model.BirthdayAnniversary
 * @copyright   2007 - 2016 (C) Joomla Bible Study Team All rights reserved
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        http://www.christianwebministries.org
 * */
defined('_JEXEC') or die;

// Always load JBSM API if it exists.
$api = JPATH_ADMINISTRATOR . '/components/com_churchdirectory/api.php';

if (file_exists($api))
{
	require_once $api;
}

// Include the Birthday/Anniversary functions only once
require_once dirname(__FILE__) . '/helper.php';

/* Set some global property */
addCSS();

/* Get the RenderHelper Class for the Module to us */
$render = new ChurchDirectoryRenderHelper;

/* Return members that have Birthdays of this month. */
$birthdays = $render->getBirthdays($params);

/* Return members that have Anniversary of this month. */
$anniversary = $render->getAnniversary($params);

/**
 * Global css
 *
 * @since   1.7.0
 * @return void
 */
function addCSS ()
{
	JHtml::stylesheet('media/com_churchdirectory/css/model.css');
	JHtml::stylesheet('media/com_churchdirectory/css/icons.css');
}

require JModuleHelper::getLayoutPath('mod_birthdayanniversary', $params->get('layout', 'default'));
