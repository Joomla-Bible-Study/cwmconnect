<?php

/**
 * Cpanel Controller
 *
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Articles list controller class.
 *
 * @package             ChurchDirectory.Admin
 * @since	1.7.0
 */
class ChurchDirectoryControllerCpanel extends JControllerAdmin {
    /**
     * Display funtion.
     */
    function display() {
        JRequest::setVar('view', 'cpanel');
        parent::display();
    }

}
