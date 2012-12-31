<?php
/**
 * @package             ChurchDirectory.Admin
 * @copyright           (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// Protect from unauthorized access
defined('_JEXEC') or die;

/**
 * Class for GeoUpdate
 * @package ChurchDirectory.Admin
 * @since 1.7.1
 */
class ChurchDirectoryViewGeoStatus extends JViewLegacy
{

	/**
	 * Protect form
	 * @var array
	 */
	protected $form;

	/**
	 * Protect items
	 * @var array
	 */
	protected $item;

	/**
	 * Protect state
	 * @var array
	 */
	protected $state;

	/**
	 * Display the view
	 * @return    void
	 */
	public function display($tpl = null)
	{
		// Set the toolbar title
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE_STATUS'), 'churchdirectory');
		$app = JFactory::getApplication()->input;

		$model = $this->getModel();
		$this->info = $model->getGeoErrors();

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		parent::display();
	}

}