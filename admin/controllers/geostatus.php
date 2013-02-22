<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Member list controller class.
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.5
 */
class ChurchDirectoryControllerGeoStatus extends JControllerAdmin
{

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.7.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
	}


	/**
	 * Method to update members geoupdate location.
	 *
	 * @return    void
	 *
	 * @since    1.7.0
	 */
	public function geoupdate()
	{
		// Check for request forgeries
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$app    = JFactory::getApplication();
		$user   = JFactory::getUser();
		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('featured' => 1, 'unfeatured' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		// Get the model.
		$model = $this->getModel();

		// Access checks.
		foreach ($ids as $i => $id)
		{
			$item = $model->getItem($id);

			if (!$user->authorise('core.edit.state', 'com_churchdirectory.category.' . (int) $item->catid))
			{
				// Prune items that you can't change.
				unset($ids[$i]);
				$app->enqueueMessage(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'notice');
			}
		}

		if (empty($ids))
		{
			$app->enqueueMessage(JText::_('COM_CHURCHDIRECTORY_NO_ITEM_SELECTED'), 'error');
		}
		else
		{
			// Publish the items.
			if (!$model->update($ids))
			{
				$app->enqueueMessage($model->getError(), 'error');
			}
		}

		$this->setRedirect('index.php?option=com_churchdirectory&view=geostatus');
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
	public function getModel($name = 'GeoStatus', $prefix = 'ChurchDirectoryModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return    void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$pks   = $this->input->post->get('cid', array(), 'array');
		$order = $this->input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}

}
