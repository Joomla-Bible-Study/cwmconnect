<?php

/**
 * @version		$Id: cpanel.php 1.7.0 $
 * @package             com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.model');

/**
 * Methods supporting a list of contact records.
 *
 * @package	com_churchdirectory
 */
class ChurchDirectoryModelCpanel extends JModel {

    /**
     * constructor (registers additional tasks to methods)
     * @return void
     */
    function __construct() {
        parent::__construct();

        // Register Extra tasks
    }

}
