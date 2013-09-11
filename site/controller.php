<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * ChurchDirectory Component Controller
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryController extends JControllerLegacy
{

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @since    1.7.0
	 * @return  JControllerLegacy  A JControllerLegacy object to support chaining.
	 */
	public function display ($cachable = false, $urlparams = array())
	{
		$cachable = true;

		if (!version_compare(JVERSION, '3.0', 'ge'))
		{
			$this->input = JFactory::getApplication()->input;
		}

		// Set the default view name and format from the Request.
		$vName = $this->input->get('view', 'categories');

		if ($vName == 'directory')
		{
			$this->input->set('tmpl', 'component');
		}
		$this->input->set('view', $vName);

		$safeurlparams = array(
			'catid'            => 'INT', 'id' => 'INT', 'cid' => 'ARRAY', 'year' => 'INT', 'month' => 'INT', 'limit' => 'UINT',
			'limitstart'       => 'UINT', 'showall' => 'INT', 'return' => 'BASE64', 'filter' => 'STRING', 'filter_order' => 'CMD',
			'filter_order_Dir' => 'CMD', 'filter-search' => 'STRING', 'print' => 'BOOLEAN', 'lang' => 'CMD');

		parent::display($cachable, $safeurlparams);

		return $this;
	}

}
