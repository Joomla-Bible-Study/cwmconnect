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

	/**
	 * Start the Update
	 *
	 * @return void
 	 */
	public function run()
	{
		$id = JFactory::getApplication()->input->getInt('id');
		if(empty($id)){
			die('no id');
		}
		$model = $this->getModel('geoupdate');
		$state = $model->run(true, $id);
		$model->setState('scanstate', $state);

		$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=cpanel', false));
	}
}
