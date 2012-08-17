<?php

/**
 * Model for Birthday and Annversary
 * @package ChurchDirectory
 * @subpackage Model.BirthdayAnniversary
 * @Copyright (C) 2007 - 2011 Joomla Bible Study Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.JoomlaBibleStudy.org
 * */
defined('_JEXEC') or die;

// Include the Birthdy/Annversary functions only once
require_once dirname(__FILE__) . '/helper.php';

/* Set some global property */
addCSS();

/* Retun members that have Birthdays and Anniversary this month. */
$birthdays = modBirthdayAnniversaryHelper::getBirthdays($params);
$anniversary = modBirthdayAnniversaryHelper::getAnniversary($params);

/**
 * Global css
 *
 * @since   1.7.0
 */
function addCSS() {
    JHtml::stylesheet('/media/com_churchdirectory/css/general.css');
    JHtml::stylesheet('/media/com_churchdirectory/css/model.css');
    JHtml::stylesheet('/media/com_churchdirectory/css/icons.css');
}

require JModuleHelper::getLayoutPath('mod_birthdayanniversary', $params->get('layout', 'default'));