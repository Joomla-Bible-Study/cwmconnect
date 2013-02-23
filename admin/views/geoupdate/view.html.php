<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Class for GeoUpdate
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.1
 */
class ChurchDirectoryViewGeoUpdate extends JViewLegacy
{

	/**
	 * Protect form
	 *
	 * @var array
	 */
	protected $form;

	/**
	 * Protect items
	 *
	 * @var array
	 */
	protected $item;

	/**
	 * Protect state
	 *
	 * @var array
	 */
	protected $state;

	protected $more;

	protected $percent;

	protected $percentage;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		// Set the toolbar title
		JToolBarHelper::title(JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE'), 'churchdirectory');

		$model = $this->getModel();
		$model->startScanning();
		$state = $model->getState('scanstate', false);

		$total = max(1, $model->totalMembers);
		$done  = $model->doneMembers;

		if ($state)
		{
			if ($total > 0)
			{
				$percent = min(max(round(100 * $done / $total), 1), 100);
			}

			$more = true;
		}
		else
		{
			$percent = 100;
			$more    = false;
		}

		$this->more = $more;
		$this->setLayout('default');

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			JHTML::_('behavior.framework');
		}
		else
		{
			JHTML::_('behavior.mootools');
		}

		/** @var $percent int Start Percentage */
		$this->percentage = $percent;

		if ($more)
		{
			$script = "window.addEvent( 'domready' ,  function() {\n";
			$script .= "document.forms.adminForm.submit();\n";
			$script .= "});\n";
			JFactory::getDocument()->addScriptDeclaration($script);
		}

		return parent::display();
	}

}
