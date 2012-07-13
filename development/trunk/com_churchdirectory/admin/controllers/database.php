<?php

/**
 * @package ChurchDirectory.Admin
 * @Copyright (C) 2007 - 2011 Joomla Bible Study Team All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.JoomlaBibleStudy.org
 * @since		1.7.0
 * */
// No direct access.
defined('_JEXEC') or die;

/**
 * Controler for Database
 * @package	ChurchDirectory.Admin
 * @since	1.7.0
 */
class ChurchDirectoryControllerDatabase extends JController {

    /**
     * Tries to fix missing database updates
     *
     * @since	7.1.0
     */
    function cancel() {
        $this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=cpanel', false));
    }

    /**
     * Tries to fix missing database updates
     *
     * @since	7.1.0
     */
    function fix() {
        $model = $this->getModel('database');
        $model->fix();
        $this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=database', false));
    }

}
