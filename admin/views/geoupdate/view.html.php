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
	protected $more;

	protected $percentage;

	/** @var array The members to process */
	private $_membersStack = array();

	/** @var int Total numbers of members in this site */
	public $totalMembers = 0;

	/** @var int Numbers of members already processed */
	public $doneMembers = 0;

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

		$total = $this->totalMembers;
		$done  = $this->doneMembers;

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

		$this->percentage = & $percent;

		if ($more)
		{
			$script = "window.addEvent( 'domready' ,  function() {\n";
			$script .= "document.forms.adminForm.submit();\n";
			$script .= "});\n";
			JFactory::getDocument()->addScriptDeclaration($script);
		}

		return parent::display($tpl);
	}

	/**
	 * Loads the file/folder stack from the session
	 *
	 * @return void
	 */
	private function loadStack()
	{
		$session = JFactory::getSession();
		$stack   = $session->get('geoupdate_stack', '', 'churchdirectory');

		if (empty($stack))
		{
			$this->_membersStack = array();
			$this->totalMembers  = 0;
			$this->doneMembers   = 0;

			return;
		}

		if (function_exists('base64_encode') && function_exists('base64_decode'))
		{
			$stack = base64_decode($stack);

			if (function_exists('gzdeflate') && function_exists('gzinflate'))
			{
				$stack = gzinflate($stack);
			}
		}
		$stack = json_decode($stack, true);

		$this->_membersStack = $stack['members'];
		$this->totalMembers  = $stack['total'];
		$this->doneMembers   = $stack['done'];
	}

}
