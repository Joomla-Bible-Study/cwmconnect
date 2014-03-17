<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2014 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * ChurchDirectory categories view.
 *
 * @package    ChurchDirectory.Site
 * @since      1.7.0
 */
class ChurchDirectoryViewCategories extends JViewLegacy
{

	/**
	 * Protected state
	 *
	 * @var object
	 */
	protected $state = null;

	/**
	 * Protected item
	 *
	 * @var object
	 */
	protected $item = null;

	/**
	 * Protected items
	 *
	 * @var array
	 */
	protected $items = null;

	/**
	 * Protected pagination
	 *
	 * @var object
	 */
	protected $pagination = null;

	protected $pageclass_sfx;

	protected $maxLevelcat;

	protected $params;

	protected $parent;

	public $document;

	/**
	 * Display the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		// Initialise variables
		$state  = $this->get('State');
		$items  = $this->get('Items');
		$parent = $this->get('Parent');
		$app    = JFactory::getApplication();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode("\n", $errors), 'warning');

			return false;
		}

		if ($items === false)
		{
			$app->enqueueMessage(JText::_('JGLOBAL_CATEGORY_NOT_FOUND'), 'error');

			return false;
		}

		if ($parent == false)
		{
			$app->enqueueMessage(404, JText::_('JGLOBAL_CATEGORY_NOT_FOUND'), 'error');

			return false;
		}

		$params = & $state->params;

		$items = array($parent->id => $items);

		// Escape strings for HTML output
		$this->pageclass_sfx = htmlspecialchars($params->get('pageclass_sfx'));

		$this->maxLevelcat = $params->get('maxLevelcat', -1);
		$this->params      = & $params;
		$this->parent      = & $parent;
		$this->items       = & $items;

		$this->_prepareDocument();

		return parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 */
	protected function _prepareDocument()
	{
		$app   = JFactory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->def('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', JText::_('COM_CHURCHDIRECTORY_DEFAULT_PAGE_TITLE'));
		}
		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}
		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}

}
