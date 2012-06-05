<?php

/**
 * @package		com_churchdirectory
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * ChurchDirectory Component Controller
 *
 * @package	com_churchdirectory
 * @since 1.7.0
 */
class ChurchDirectoryController extends JController {

    /**
     * Method to display a view.
     *
     * @param	boolean			If true, the view output will be cached
     * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.7.0
     */
    public function display($cachable = false, $urlparams = false) {
        $cachable = true;

        // Get the document object.
        $document = JFactory::getDocument();

        // Set the default view name and format from the Request.
        $vName = JRequest::getCmd('view', 'categories');
        JRequest::setVar('view', $vName);

        $user = JFactory::getUser();

        $safeurlparams = array('catid' => 'INT', 'id' => 'INT', 'cid' => 'ARRAY', 'year' => 'INT', 'month' => 'INT', 'limit' => 'INT', 'limitstart' => 'INT',
            'showall' => 'INT', 'return' => 'BASE64', 'filter' => 'STRING', 'filter_order' => 'CMD', 'filter_order_Dir' => 'CMD', 'filter-search' => 'STRING', 'print' => 'BOOLEAN', 'lang' => 'CMD');

        parent::display($cachable, $safeurlparams);

        return $this;
    }

}
