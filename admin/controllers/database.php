<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.christianwebministries.org
 * @since      1.7.0
 * */

defined('_JEXEC') or die;

/**
 * Controller for Database
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryControllerDatabase extends JControllerLegacy
{
	/**
	 * Tries to fix missing database updates
	 *
	 * @since    7.1.0
	 * @return void
	 */
	public function cancel()
	{
		$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=cpanel', false));
	}

	/**
	 * Tries to fix missing database updates
	 *
	 * @since    7.1.0
	 * @return void
	 */
	public function fix()
	{
		$model = $this->getModel('database');
		$model->fix();
		$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=database', false));
	}
}
