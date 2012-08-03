<?php

/**
 * Model for ChurchDirectory Birthday & Anniversary Display.
 * @package		ChurchDirectory.Site
 * @subpackage	mod_birthdayanniversary
 * @copyright	Copyright (C) 2012
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

require_once JPATH_SITE . '/components/com_churchdirectory/helpers/route.php';

JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_churchdirectory/models', 'ChurchDirectoryModel');

/**
 * Abstract helper for Birthdy Anniversary Display
 * @package ChurchDirectory.Site
 * @subpackage mod_birthdayanniversary
 * @since 1.7.2
 */
abstract class modBirthdayAnniversaryHelper {

    /**
     * Get List
     * @param array $params
     */
    public static function getList(&$params) {
        // Get an instance of the generic Members model
        $model = JModelLegacy::getInstance('Directory', 'ChurchDirectoryModel', array('ignore_request' => true));

        // Set application parameters in model
        $app = JFactory::getApplication();
        $appParams = $app->getParams();
        $model->setState('params', $appParams);

        // Set the filters based on the module params
        $model->setState('list.start', 0);
        $model->setState('list.limit', (int) $params->get('count', 5));

        $model->setState('filter.state', 1);
        $model->setState('filter.archived', 0);
        $model->setState('filter.approved', 1);

        // Access filter
        $access = !JComponentHelper::getParams('com_churchdirectory')->get('show_noauth');
        $authorised = JAccess::getAuthorisedViewLevels(JFactory::getUser()->get('id'));
        $model->setState('filter.access', $access);

        $ordering = $params->get('ordering', 'ordering');
        $model->setState('list.ordering', $ordering == 'order' ? 'ordering' : $ordering);
        $model->setState('list.direction', $params->get('direction', 'asc'));

        $catid = (int) $params->get('catid', 0);
        $model->setState('category.id', $catid);
    }

}