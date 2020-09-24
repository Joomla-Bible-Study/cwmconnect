<?php
/**
 * Directory view for ChurchDirectory
 *
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

require_once JPATH_COMPONENT . '/models/category.php';

/**
 * HTML Member View class for the ChurchDirectory component
 *
 * @property  JFactory::getDocument document
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewDirectory extends JViewLegacy
{
	/**
	 * Protected @var object
	 *
	 * @since       1.7.2
	 */
	protected $category = null;

	/** @var  Joomla\Registry\Registry */
	protected $params;

	/**
	 * Protected @var object
	 *
	 * @since       1.7.2
	 */
	protected $state = null;

	/**
	 * Protected @var array
	 *
	 * @since       1.7.2
	 */
	protected $items = null;

	/**
	 * Protected @var array
	 *
	 * @since       1.7.2
	 */
	protected $categories = null;

	/**
	 * Protected  @var array
	 *
	 * @since       1.7.2
	 */
	protected $pagination = null;

	protected $span;

	protected $maxLevel;

	protected $children;

	protected $parent;

	protected $header;

	protected $renderHelper;

	protected $count;

	/**
	 * @var  mPDF
	 * @since version
	 */
	protected $pdf;

	public $printed_items;

	public $printed_rows;

	public $subcount;

	public $letter;

	public $baseurl;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since       1.7.2
	 */
	public function display($tpl = null)
	{
		$this->params   = JComponentHelper::getParams('com_churchdirectory');
		$this->category = $this->get('Category');

		$this->prepareDocument();

		$layout = JFactory::getApplication()->input->get('layout', 'home');

		if ($layout === 'search')
		{
			// Search Params
			$registry   = new Registry;
			$registry->set('opensearch', '1');
			$registry->set('size-lbl', '12');
			$registry->set('show_button', '1');
			$registry->set('button_pos', 'right');
			$this->params->merge($registry);

			$this->renderHelper = new ChurchDirectoryRenderHelper;

			// Get some data from the models
			$this->items    = $this->get('Search');
		}

		$this->setLayout($layout);

		return parent::display();
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @since       1.7.2
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
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', JText::_('COM_CHURCHDIRECTORY_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->category->metadesc)
		{
			$this->document->setDescription($this->category->metadesc);
		}
		elseif (!$this->category->metadesc && $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->category->metakey)
		{
			$this->document->setMetaData('keywords', $this->category->metakey);
		}
		elseif (!$this->category->metakey && $this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetaData('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetaData('robots', $this->params->get('robots'));
		}
	}
}
