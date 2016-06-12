<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * class for Member
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.0
 */
class ChurchDirectoryControllerMember extends JControllerForm
{

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return    boolean
	 *
	 * @since    1.7.0
	 */
	protected function allowAdd($data = array())
	{
		// Initialise variables.
		$user       = JFactory::getUser();
		$categoryId = Joomla\Utilities\ArrayHelper::getValue($data, 'catid', JFactory::getApplication()->input->getInt('filter_category_id', 0), 'int');
		$allow      = null;

		if ($categoryId)
		{
			// If the category has been passed in the URL check it.
			$allow = $user->authorise('core.create', $this->option . '.category.' . $categoryId);
		}

		if ($allow === null)
		{
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd();
		}
		else
		{
			return $allow;
		}
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		// Initialise variables.
		$recordId   = (int) isset($data[$key]) ? $data[$key] : 0;
		$categoryId = 0;

		if ($recordId)
		{
			$categoryId = (int) $this->getModel()->getItem($recordId)->catid;
		}

		if ($categoryId)
		{
			// The category has been set. Check the category permissions.
			return JFactory::getUser()->authorise('core.edit', $this->option . '.category.' . $categoryId);
		}
		else
		{
			// Since there is no asset tracking, revert to the component permissions.
			return parent::allowEdit($data, $key);
		}
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   JModelLegacy  $model  The model of the component being processed.
	 *
	 * @return    void
	 *
	 * @since    1.6
	 */
	public function batch($model)
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		/** @var $model JModelLegacy */
		$model = $this->getModel('Member', '', array());

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=members' . $this->getRedirectToListAppend(), false));

		parent::batch($model);
	}

}
