<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  Copyright (C) 2005 - 2016 Joomla Bible Study, All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * HTML Contact View class for the Contact component
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewReports extends JViewLegacy
{
	/**
	 * Protected
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $state;

	/**
	 * Display function
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since       1.7.2
	 */
	public function display ($tpl = null)
	{
		// Check for request forgeries.
		JSession::checkToken('get') or JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		/** @var ChurchDirectoryModelReports $module */
		$module = $this->getModel();

		$input  = JFactory::getApplication()->input;
		$cdtype = $input->getCmd('cdtype');
		$name   = $input->getCmd('name', 'Testfile');

		switch ($cdtype)
		{
			case 'kml':
				$module->getExport('kml', $name);
				break;
			case 'pdf':
				$module->getExport('pdf', $name);
				break;
			case 'missingphotos':
				$module->getExport('missingphotos', $name);
				break;
		}

		return true;
	}
}
