<?php
/**
 * View for Category
 *
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * HTML View class for the ChurchDirectorys component
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryViewCategory extends JViewCategory
{
	/**
	 * @var    string  The name of the extension for the category
	 * @since  3.2
	 */
	protected  $extension = 'com_churchdirectory';

	/**
	 * @var    string  Default title to use for page title
	 * @since  3.2
	 */
	protected  $defaultPageTitle = 'COM_CHURCHDIRECTORY_DEFAULT_PAGE_TITLE';

	/**
	 * @var    string  The name of the view to link individual items to
	 * @since  3.2
	 */
	protected $viewName = 'member';

	/**
	 * Run the standard Joomla plugins
	 *
	 * @var    bool
	 * @since  3.5
	 */
	protected $runPlugins = true;

	/**
	 * Protected state
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $state;

	/**
	 * Protected items
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $items;

	/**
	 * Protected category
	 *
	 * @var object
	 * @since       1.7.2
	 */
	protected $category;

	/**
	 * Protected categories
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $categories;

	/**
	 * Protected pagination
	 *
	 * @var array
	 * @since       1.7.2
	 */
	protected $pagination;

	/**
	 * @var  \Joomla\Registry\Registry
	 * @since 1.7.8
	 */
	public $params;

	public $menu;

	/**
	 * @var  JPathway|null
	 * @since 1.7.8
	 */
	public $pathway;

	/**
	 * Display Function
	 *
	 * @param   string  $tpl  ?
	 *
	 * @return boolean
	 *
	 * @since       1.7.2
	 */
	public function display($tpl = null)
	{
		parent::commonCategoryDisplay();

		// Prepare the data.
		// Compute the contact slug.
		foreach ($this->items as $item)
		{
			$item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
			$temp       = $item->params;
			$item->params = clone $this->params;
			$item->params->merge($temp);

			if ($item->params->get('show_email_headings', 0) == 1)
			{
				$item->email_to = trim($item->email_to);

				if (!empty($item->email_to) && JMailHelper::isEmailAddress($item->email_to))
				{
					$item->email_to = JHtml::_('email.cloak', $item->email_to);
				}
				else
				{
					$item->email_to = '';
				}
			}
		}

		$this->renderHelper = new ChurchDirectoryRenderHelper;

		return parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void;
	 *
	 * @since       1.7.2
	 */
	protected function _prepareDocument()
	{
		parent::prepareDocument();

		$menu = $this->menu;
		$id = (int) @$menu->query['id'];

		if ($menu && ($menu->query['option'] != $this->extension || $menu->query['view'] == $this->viewName || $id != $this->category->id))
		{
			$path = array(array('title' => $this->category->title, 'link' => ''));
			$category = $this->category->getParent();

			while (($menu->query['option'] !== 'com_contact' || $menu->query['view'] === 'contact' || $id != $category->id) && $category->id > 1)
			{
				$path[] = array('title' => $category->title, 'link' => ChurchDirectoryHelperRoute::getCategoryRoute($category->id));
				$category = $category->getParent();
			}

			$path = array_reverse($path);

			foreach ($path as $item)
			{
				$this->pathway->addItem($item['title'], $item['link']);
			}
		}

		parent::addFeed();
	}
}
