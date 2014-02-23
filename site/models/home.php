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
class ChurchDirectoryModelHome extends JModelForm
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

		$this->setState('return_page', 'index.php?option=com_churchdirectory');

		// Load the parameters.
		$params     = $app->getParams();
		$this->setState('params', $params);
	}

	/**
	 * Method to get the member form.
	 *
	 * The base form is loaded from XML and then an event is fired
	 *
	 * @param    array   $data     An optional array of data for the form to interrogate.
	 * @param    boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return    JForm    A JForm object on success, false on failure
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_churchdirectory.home', 'home', array('control' => 'jform', 'load_data' => true));

		if (empty($form))
		{
			return false;
		}

		$id     = (int) $this->getState('home.id');
		$params = $this->getState('params');
		$member = $this->_item[$id];
		$params->merge($member->params);

		if (!$params->get('show_email_copy', 0))
		{
			$form->removeField('member_email_copy');
		}

		return $form;
	}

	/**
	 * Load form date
	 *
	 * @return array
	 */
	protected function loadFormData()
	{
		$data = (array) JFactory::getApplication()->getUserState('com_churchdirectory.home.data', array());

		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			$this->preprocessData('com_churchdirectory.home', $data);
		}

		return $data;
	}

	/**
	 * Gets a list of members
	 *
	 * @param   int $pk Id of member
	 *
	 * @return mixed Object or null
	 */
	public function &getItem($pk = null)
	{
		return $pk;
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

}
