<?php

/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @package             ChurchDirectory.Admin
 * @since               1.7.0
 */
class ChurchDirectoryControllerDirectory extends JControllerAdmin {
    /**
     * Display
     */
    function display() {
        JRequest::setVar('view', 'directory');
        parent::display();
    }

}