<?php
/**
 * @package        ChurchDirectory.Site
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * HTML Home View class for the ChurchDirectory component
 *
 * @package       ChurchDirectory.Site
 * @since         1.7.0
 */
class ChurchDirectoryViewHome extends JViewLegacy
{

	protected $state;

	protected $item;

	protected $params;

	protected $user;

	protected $return;

	/**
	 * Display function
	 *
	 * @param string $tpl
	 *
	 * @return boolean
	 */
	public function display($tpl = null)
	{
		$app          = JFactory::getApplication();
		$user         = JFactory::getUser();
		$state        = $this->get('State');
		$item         = $this->get('Item');
		$this->return = $this->get('ReturnPage');

		// Get the parameters
		$params = JComponentHelper::getParams('com_churchdirectory');
		$params->merge($state->params);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode("\n", $errors), 'error');

			return false;
		}

		$this->params = & $params;
		$this->user   = & $user;
		$this->item   = & $item;
		$this->prepareDocument();
		parent::display($tpl);

	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 */
	protected function prepareDocument()
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
