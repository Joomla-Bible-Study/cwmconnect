<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Utilities\ArrayHelper;

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
	 * @throws   Exception
	 */
	protected function allowAdd($data = [])
	{
		// Initialise variables.
		$user       = JFactory::getUser();
		$categoryId = ArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');
		$allow      = null;

		if ($categoryId)
		{
			// If the category has been passed in the URL check it.
			$allow = $user->authorise('core.create', $this->option . '.category.' . $categoryId);
		}

		if ($allow === null)
		{
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd($data);
		}

		return $allow;
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
	protected function allowEdit($data = [], $key = 'id')
	{
		// Initialise variables.
		$recordId   = (int) isset($data[$key]) ? $data[$key] : 0;

		// Since there is no asset tracking, fallback to the component permissions.
		if (!$recordId)
		{
			return parent::allowEdit($data, $key);
		}

		// Get the item.
		$item = $this->getModel()->getItem($recordId);

		// Since there is no item, return false.
		if (empty($item))
		{
			return false;
		}

		$user = JFactory::getUser();

		// Check if can edit own core.edit.own.
		$canEditOwn = $user->authorise('core.edit.own', $this->option . '.category.' . (int) $item->catid) && $item->created_by == $user->id;

		// Check the category core.edit permissions.
		return $canEditOwn || $user->authorise('core.edit', $this->option . '.category.' . (int) $item->catid);
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
		$model = $this->getModel('Member', '', []);

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_churchdirectory&view=members' . $this->getRedirectToListAppend(), false));

		parent::batch($model);
	}
}
