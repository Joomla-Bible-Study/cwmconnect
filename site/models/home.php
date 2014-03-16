<?php
/**
 * @package        ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die;

jimport('joomla.application.component.modelform');
jimport('joomla.event.dispatcher');

/**
 * Module for Members
 *
 * @package  ChurchDirectory.Site
 * @since    2.5
 */
class ChurchDirectoryModelHome extends JModelItem
{

	/**
	 * Protect view
	 *
	 * @var string
	 */
	protected $view_item = 'Home';

	/**
	 * Protect item
	 *
	 * @var int
	 */
	protected $_item = null;

	protected $member;

	/**
	 * Model context string.
	 *
	 * @var        string
	 */
	protected $_context = 'com_churchdirectory.home';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since    1.6
	 * @return  void
	 */
	protected function populateState()
	{
		$app   = JFactory::getApplication('site');


		$return = $app->input->get('return', $this->setReturnPage(), 'base64');
		$this->setState('return_page', base64_decode($return));

		// Load the parameters.
		$params     = $app->getParams();
		$this->setState('params', $params);
	}

	/**
	 * Get the return URL.
	 *
	 * @return    string    The return URL.
	 *
	 * @since    1.6
	 */
	public function getReturnPage()
	{
		return base64_encode($this->getState('return_page'));
	}

	/**
	 * Set Return Page if non passed
	 *
	 * @return string URL of current page.
	 */
	public function setReturnPage()
	{
		$Itemid = JFactory::getApplication()->input->getInt('Itemid');
		if ($Itemid)
		{
			$Itemid = '&Itemid=' . $Itemid;
		}
		return base64_encode('index.php?option=' . $this->option . '&view=' . $this->view_item . $Itemid);
	}

}
