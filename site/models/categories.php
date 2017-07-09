<?php
/**
 * @package    ChurchDirectory.Site
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * This models supports retrieving lists of churchdirectory categories.
 *
 * @package  ChurchDirectory.Site
 * @since    1.7.0
 */
class ChurchDirectoryModelCategories extends JModelList
{
	/**
	 * Model context string.
	 *
	 * @var        string
	 * @since       1.7.2
	 */
	public $context = 'com_churchdirectory.categories';

	/**
	 * The category context (allows other extensions to derived from this model).
	 *
	 * @var        string
	 * @since       1.7.2
	 */
	protected $extension = 'com_churchdirectory';

	/**
	 * The parent context
	 *
	 * @var array
	 * @since       1.7.2
	 */
	private $parent = null;

	/**
	 * The items
	 *
	 * @var array
	 * @since       1.7.2
	 */
	private $items = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since       1.7.2
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();
		$this->setState('filter.extension', $this->extension);

		// Get the parent id if defined.
		$parentId = $app->input->getInt('id');
		$this->setState('filter.parentId', $parentId);

		$params = $app->getParams();
		$this->setState('params', $params);

		$this->setState('filter.published', 1);
		$this->setState('filter.access', true);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since       1.7.2
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.extension');
		$id .= ':' . $this->getState('filter.published');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.parentId');

		return parent::getStoreId($id);
	}

	/**
	 * redefine the function an add some properties to make the styling more easy
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since       1.7.2
	 */
	public function getItems()
	{
		if (!count($this->items))
		{
			$app    = JFactory::getApplication();
			$menu   = $app->getMenu();
			$active = $menu->getActive();
			$params = new Registry;

			if ($active)
			{
				$params->loadString($active->params);
			}

			$options               = [];
			$options['countItems'] = $params->get('show_cat_items_cat', 1) || !$params->get('show_empty_categories_cat', 0);
			$categories            = JCategories::getInstance('ChurchDirectory', $options);
			$this->parent          = $categories->get($this->getState('filter.parentId', 'root'));

			if (is_object($this->parent))
			{
				$this->items = $this->parent->getChildren();
			}
			else
			{
				$this->items = false;
			}
		}

		return $this->items;
	}

	/**
	 * Get parent
	 *
	 * @return  array
	 *
	 * @since       1.7.2
	 */
	public function getParent()
	{
		if (!is_object($this->parent))
		{
			$this->getItems();
		}

		return $this->parent;
	}
}
