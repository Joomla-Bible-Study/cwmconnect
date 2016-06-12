<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

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
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  Ingnore info
	 *
	 * @return    ChurchDirectoryModelReports
	 *
	 * @since    1.7.0
	 */
	public function getModel($name = 'Reports', $prefix = 'ChurchDirectoryModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Report Export to CSV
	 *
	 * @return void
	 */
	public function export()
	{

		$jweb = new JApplicationWeb;
		$report = $jweb->input->get('report');

		$date = new JDate('now');

		// Clean the output buffer
		@ob_end_clean();
		$jweb->clearHeaders();

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=report." . $report . '.' . $date->format('Y-m-d-His') . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$this->getModel()->getCsv();

		jexit();
	}

}
