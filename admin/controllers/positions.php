<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Positions list controller class.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryControllerPositions extends JControllerAdmin
{

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	/**
	 * Method to toggle the featured setting of a list of memberss.
	 *
	 * @return    void
	 *
	 * @since    1.7.0
	 */
	public function featured()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
		$user  = JFactory::getUser();
		$app   = JFactory::getApplication();
		$ids   = $app->input->get('id', array(), 'array');
		$task  = $this->getTask();
		$value = Joomla\Utilities\ArrayHelper::getValue($values, $task, 0, 'int');

		// Get the model.
		$model = $this->getModel();

		// Access checks.
		foreach ($ids as $i => $id)
		{

			if (!$user->authorise('core.edit.state'))
			{
				// Prune items that you can't change.
				unset($ids[$i]);
				$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'notice');
			}
		}

		if (empty($ids))
		{
			$app->enqueueMessage(JText::_('COM_CHURCHDIRECTORY_NO_ITEM_SELECTED'), 'warning');
		}
		else
		{
			// Publish the items.
			if (!$model->featured($ids, $value))
			{
				$app->enqueueMessage($model->getError(), 'warning');
			}
		}

		$this->setRedirect('index.php?option=com_churchdirectory&view=postions');
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  The prefix for the PHP class name.
	 * @param   array   $config  Ingnore info
	 *
	 * @return    JModel
	 *
	 * @since    1.7.0
	 */
	public function getModel($name = 'Position', $prefix = 'ChurchDirectoryModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

}
