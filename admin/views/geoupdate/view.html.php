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
class ChurchDirectoryViewGeoUpdate extends JViewLegacy
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

	public $more;

	public $percent;

	/**
	 * Display the view
	 * @return    void
	 */
	public function display($tpl = null)
	{
		// Set the toolbar title
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE'), 'churchdirectory');
		$app = JFactory::getApplication()->input;

		$model = $this->getModel();
		$state1 = $model->startScanning();
		$model->setState('scanstate', $state1);
		$state2 = $model->run();
		$model->setState('scanstate', $state2);
		$state = $model->getState('scanstate');

		$total = max(1, $model->totalMembers);
		$done = $model->doneMembers;

		$layout = $app->getString('layout', 'default');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			JError::raiseError(500, implode("\n", $errors));
			return false;
		}

		if($state)
		{
			if($total > 0)
			{
				$percent = min(max(round(100 * $done / $total),1),100);
			}

			$more = true;
		}
		else
		{
			$percent = 100;
			$more = false;
		}

		$this->more = $more;

		/** @var $percent Start Percentage */
		$this->percentage = $percent;

		$this->setLayout($layout);

		if(version_compare(JVERSION, '3.0', 'ge')) {
			JHTML::_('behavior.framework');
		} else {
			JHTML::_('behavior.mootools');
		}

		if($more) {
			$script = "window.addEvent( 'domready' ,  function() {\n";
			$script .= "document.forms.adminForm.submit();\n";
			$script .= "});\n";
			JFactory::getDocument()->addScriptDeclaration($script);
		}

		parent::display();
	}

}