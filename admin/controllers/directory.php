<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * ChurchDirectory Member manager component for Joomla!
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryControllerDirectory extends JControllerAdmin
{
	/**
	 * Display
	 *
	 * @return void
	 */
	public function display()
	{
		JFactory::getApplication()->input->set('view', 'directory');
		parent::display();
	}

}
