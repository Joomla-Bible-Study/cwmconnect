<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bcordis
 * Date: 12/9/12
 * Time: 4:08 PM
 * To change this template use File | Settings | File Templates.
 */

class ChurchDirectoryControllerGeoupdate extends JControllerAdmin
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->modelName = 'geoupdate';
		die;
	}

	public function execute($task)
	{
		die;
		if ($task != 'run') $task = 'browse';
		parent::execute($task);
	}

	public function browse()
	{
		$model = $this->getModel('geoupdate');
		$state = $model->startScanning();
		$model->setState('scanstate', $state);

		$this->display(false);
	}

	public function run()
	{
		die;
		$model = $this->getModel('geoupdate');
		$state = $model->run();
		$model->setState('scanstate', $state);

		$this->display(false);
	}
}