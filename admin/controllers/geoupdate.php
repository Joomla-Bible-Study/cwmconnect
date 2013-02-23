<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

jimport('joomla.application.component.controlleradmin');
/**
 * Class for GeoUpdate
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.5
 */
class ChurchDirectoryControllerGeoupdate extends JControllerAdmin
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->modelName = 'geoupdate';
	}

	/**
	 * Constructor.
	 *
	 * @param   string  $task  An optional associative array of configuration settings.
	 *
	 * @return void
	 */
	public function execute($task)
	{
		if ($task != 'run')
		{
			$task = 'browse';
		}
		parent::execute($task);
	}

	/**
	 * Constructor.
	 *
	 * @param   int  $id  Id of member
	 *
	 * @return void
	 */
	public function browse($id = null)
	{
		if (empty($id))
		{
			$id = JFactory::getApplication()->input->getInt('id');
		}
		$model = $this->getModel('geoupdate');
		$state = $model->startScanning($id);
		$model->setState('scanstate', $state);

		$this->display(false);
	}

	/**
	 * Start the Update
	 *
	 * @return void
	 */
	public function run()
	{
		$id = JFactory::getApplication()->input->getInt('id');

		$model = $this->getModel('geoupdate');
		$state = $model->run(true, $id);
		$model->setState('scanstate', $state);

		$this->display(false);
	}
}
