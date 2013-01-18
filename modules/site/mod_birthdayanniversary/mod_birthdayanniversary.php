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

/* Retun members that have Birthdays of this month. */
$birthdays = modBirthdayAnniversaryHelper::getBirthdays($params);

/* Retun members that have Anniversary of this month. */
$anniversary = modBirthdayAnniversaryHelper::getAnniversary($params);

//Get Groups infore for checking permisions
$user = JFactory::getUser();
$groups = $user->getAuthorisedViewLevels();

//check permissions for Birthdays by running through the records and removing those the user doesn't have permission to see
$count = count($birthdays);
for ($i = 0; $i < $count; $i++) {
    if ($birthdays[$i]['access'] > 1):
        if (!in_array($birthdays[$i]['access'], $groups)) {
            unset($birthdays[$i]);
        }
    endif;
}

//check permissions for Anniversaries by running through the records and removing those the user doesn't have permission to see
$count = count($anniversary);
for ($i = 0; $i < $count; $i++) {
    if ($anniversary[$i]['access'] > 1):
        if (!in_array($anniversary[$i]['access'], $groups)) {
            unset($anniversary[$i]);
        }
    endif;
}

/**
 * Global css
 *
 * @since   1.7.0
 */
function addCSS() {
    JHtml::stylesheet('media/com_churchdirectory/css/general.css');
    JHtml::stylesheet('media/com_churchdirectory/css/model.css');
    JHtml::stylesheet('media/com_churchdirectory/css/icons.css');
}

require JModuleHelper::getLayoutPath('mod_birthdayanniversary', $params->get('layout', 'default'));