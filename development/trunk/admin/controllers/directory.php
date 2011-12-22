<?php

/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @version             $Id: directory.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           Copyright (C) 2005 - 2011 All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

class ChurchDirectoryControllerDirectory extends JControllerAdmin {

    function display() {
        JRequest::setVar('view', 'directory');
        parent::display();
    }

}