<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Class for GeoUpdate
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.5
 */
class ChurchDirectoryControllerGeoupdate extends JControllerForm
{
	/**
	 * The context for storing internal data, e.g. record.
	 *
	 * @var    string
	 * @since  12.2
	 */
	protected $context = 'geoupdate';

	/**
	 * The URL view item variable.
	 *
	 * @var    string
	 * @since  12.2
	 */
	protected $view_item = 'geoupdate';

	/**
	 * The URL view list variable.
	 *
	 * @var    string
	 * @since  12.2
	 */
	protected $view_list = 'geoupdate';

	/**
	 * Execute.
	 *
	 * @param   string  $task  An optional associative array of configuration settings.
	 *
	 * @return void
	 *
	 * @since    1.7.0
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
	 *
	 * @since    1.7.0
	 */
	public function browse($id = null)
	{
		$app = JFactory::getApplication();

		if (empty($id))
		{
			$id = $app->input->getInt('id', 0);
		}

		/** @var ChurchDirectoryModelGeoUpdate $model */
		$model = $this->getModel('geoupdate');
		$state = $model->startScanning($id);
		$app->input->set('scanstate', $state);

		$this->display(false);
	}

	/**
	 * Start the Update
	 *
	 * @return void
	 *
	 * @since 1.7.0
	 */
	public function run()
	{
		$app = JFactory::getApplication();
		$id = $app->input->getInt('id', 0);

		/** @var ChurchDirectoryModelGeoUpdate $model */
		$model = $this->getModel('geoupdate');
		$state = $model->run(true, $id);
		$app->input->set('scanstate', $state);

		$this->display(false);
	}
}
