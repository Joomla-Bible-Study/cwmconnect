<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Reports list controller class.
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
	 *
	 * @since    1.7.0
	 */
	public function display($cachable = false, $urlparams = [])
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
	public function getModel($name = 'Reports', $prefix = 'ChurchDirectoryModel', $config = ['ignore_request' => true])
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Report Export
	 *
	 * @return void
	 *
	 * @since    1.7.0
	 */
	public function export()
	{
		$jWeb = new JApplicationWeb;
		$report = $jWeb->input->get('report');
		$type   = $jWeb->input->get('cdtype');

		$this->getModel()->getExport($type, $report);

		return null;
	}
}
