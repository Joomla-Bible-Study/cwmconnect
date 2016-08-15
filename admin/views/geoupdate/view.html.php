<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
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
	 * @var array The pre versions to process
	 * @since    1.7.0
	 */
	private $membersStack = [];

	/**
	 * @var int Total numbers of Versions
	 * @since    1.7.0
	 */
	public $totalMembers = 0;

	/**
	 * @var int Numbers of Versions already processed
	 * @since    1.7.0
	 */
	public $doneMembers = 0;

	/**
	 * @var string Running Now
	 * @since    1.7.0
	 */
	public $running = null;

	/**
	 * @var array Call stack for the Visioning System.
	 * @since    1.7.0
	 */
	public $callstack = [];

	/**
	 * @var string More
	 * @since    1.7.0
	 */
	protected $more;

	/**
	 * @var  string Percentage
	 * @since    1.7.0
	 */
	protected $percentage;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since    1.7.0
	 */
	public function display($tpl = null)
	{
		// Set the toolbar title
		JToolbarHelper::title(JText::_('COM_CHURCHDIRECTORY_TITLE_GEOUPDATE'), 'churchdirectory');
		$app   = JFactory::getApplication();
		$state = $app->input->getBool('scanstate', false);
		$this->loadStack();

		if ($state)
		{
			if ($this->totalMembers > 0)
			{
				$count = ($this->doneMembers / $this->totalMembers) * 100;
				$percent = (int) number_format($count, 0);
				$percent++;
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
	 *
	 * @since    1.7.0
	 */
	private function loadStack()
	{
		$session = JFactory::getSession();
		$stack   = $session->get('geoupdate_stack', '', 'churchdirectory');

		if (empty($stack))
		{
			$this->membersStack = [];
			$this->totalMembers = 0;
			$this->doneMembers  = 0;

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

		$this->membersStack = $stack['members'];
		$this->totalMembers = $stack['total'];
		$this->doneMembers  = $stack['done'];
	}
}
