<?php

/**
 * Church Direcotry component for Joomla! 1.7
 *
 * @version 1.0.0
 * @package com_churchdirectory
 * @author Nashville First SDA Church
 * @copyright Copyright (C) 2011.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.view' );

class ChurchDirectoryViewInfo extends JView
{
        protected $items;
	protected $pagination;
	protected $state;

	public function display($tpl = null)
	{
                $this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->state		= $this->get('State');

                // Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

            $this->addToolbar();
            parent::display($tpl);
	}
        /**
	 * Add the page title and toolbar.
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
            require_once JPATH_COMPONENT.'/helpers/churchdirectory.php';
		$user	= JFactory::getUser();
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_MANAGER_INFO'), 'contact.png');


		JToolBarHelper::help('churchdirectory', TRUE);
        }
}
