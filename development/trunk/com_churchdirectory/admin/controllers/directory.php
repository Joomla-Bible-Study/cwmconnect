<?php

/**
 * ChurchDirectory Contact manager component for Joomla!
 *
 * @version             $Id: directory.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

class ChurchDirectoryControllerDirectory extends JControllerAdmin {

    function display() {
        JRequest::setVar('view', 'directory');
        parent::display();
    }

}