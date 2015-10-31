<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Articles list controller class.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryControllerReports extends JControllerAdmin
{

	/**
	 * Display funtion.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JControllerLegacy  A JControllerLegacy object to support chaining.
	 */
	public function display($cachable = false, $urlparams = array())
	{
		JFactory::getApplication()->input->set('view', 'reports');
		parent::display();
	}

	/**
	 * Report Export to CSV
	 *
	 * @param   string  $report  String the name of the report.
	 *
	 * @return void
	 */
	public function export($report = 'new')
	{
		// Clean the output buffer
		@ob_end_clean();

		$jweb = new JApplicationWeb;
		$jweb->clearHeaders();

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=report." . $report . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$this->getModel()->getCsv();

		jexit();
	}

}
