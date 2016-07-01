<?php
/**
 * @package    Finder.ChurchDirectory
 * @copyright  2007 - 2016 (C) Joomla Bible Study Team All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

use Joomla\Registry\Registry;

// Load the base adapter.
require_once JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php';

/**
 * Finder adapter for ChurchDirectory Members.
 *
 * @package  Finder.ChurchDirectory
 * @since    1.7.0
 */
class PlgFinderChurchDirectory extends FinderIndexerAdapter
{

	/**
	 * The plugin identifier.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $context = 'Churchdirectory';

	/**
	 * The extension name.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $extension = 'com_churchdirectory';

	/**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $layout = 'member';

	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $type_title = 'Church Member';

	/**
	 * The table name.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $table = '#__churchdirectory_details';

	/**
	 * The field the published state is stored in.
	 *
	 * @var    string
	 * @since  1.7.0
	 */
	protected $state_field = 'published';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An array that holds the plugin configuration
	 *
	 * @since   2.5
	 */
	public function __construct (&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * Method to update the item link information when the item category is
	 * changed. This is fired when the item category is published or unpublished
	 * from the list view.
	 *
	 * @param   string   $extension  The extension whose category has been updated.
	 * @param   array    $pks        A list of primary key ids of the content that has changed state.
	 * @param   integer  $value      The value of the state that the content has been changed to.
	 *
	 * @return  void
	 *
	 * @since   1.7.0
	 */
	public function onFinderCategoryChangeState ($extension, $pks, $value)
	{
		// Make sure we're handling com_churchdirectory categories
		if ($extension == 'com_churchdirectory')
		{
			$this->categoryStateChange($pks, $value);
		}
	}

	/**
	 * Method to remove the link information for items that have been deleted.
	 *
	 * This event will fire when ChurchDirectory are deleted and when an indexed item is deleted.
	 *
	 * @param   string  $context  The context of the action being performed.
	 * @param   JTable  $table    A JTable object containing the record to be deleted
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 * @throws  Exception on database error.
	 */
	public function onFinderAfterDelete ($context, $table)
	{
		if ($context == 'com_churchdirectory.member')
		{
			$id = $table->id;
		}
		elseif ($context == 'com_finder.index')
		{
			$id = $table->link_id;
		}
		else
		{
			return true;
		}

		// Remove the items.
		return $this->remove($id);
	}

	/**
	 * Method to determine if the access level of an item changed.
	 *
	 * @param   string   $context  The context of the content passed to the plugin.
	 * @param   JTable   $row      A JTable object
	 * @param   boolean  $isNew    If the content has just been created
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 * @throws  Exception on database error.
	 */
	public function onFinderAfterSave ($context, $row, $isNew)
	{
		// We only want to handle churchdirectory here
		if ($context == 'com_churchdirectory.member')
		{
			// Check if the access levels are different
			if (!$isNew && $this->old_access != $row->access)
			{
				// Process the change.
				$this->itemAccessChange($row);
			}

			// Reindex the item
			$this->reindex($row->id);
		}

		// Check for access changes in the category
		if ($context == 'com_categories.category')
		{
			// Check if the access levels are different
			if (!$isNew && $this->old_cataccess != $row->access)
			{
				$this->categoryAccessChange($row);
			}
		}

		return true;
	}

	/**
	 * Method to reindex the link information for an item that has been saved.
	 * This event is fired before the data is actually saved so we are going
	 * to queue the item to be indexed later.
	 *
	 * @param   string   $context  The context of the content passed to the plugin.
	 * @param   JTable   $row      A JTable object
	 * @param   boolean  $isNew    If the content is just about to be created
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 * @throws  Exception on database error.
	 */
	public function onFinderBeforeSave ($context, $row, $isNew)
	{
		// We only want to handle members here
		if ($context == 'com_churchdirectory.member')
		{
			// Query the database for the old access level if the item isn't new
			if (!$isNew)
			{
				$this->checkItemAccess($row);
			}
		}

		// Check for access levels from the category
		if ($context == 'com_categories.category')
		{
			// Query the database for the old access level if the item isn't new
			if (!$isNew)
			{
				$this->checkCategoryAccess($row);
			}
		}

		return true;
	}

	/**
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished, archived, or unarchived from the list view.
	 *
	 * @param   string   $context  The context for the content passed to the plugin.
	 * @param   array    $pks      A list of primary key ids of the content that has changed state.
	 * @param   integer  $value    The value of the state that the content has been changed to.
	 *
	 * @return  void
	 *
	 * @since   1.7.0
	 */
	public function onFinderChangeState ($context, $pks, $value)
	{
		// We only want to handle members here
		if ($context == 'com_churchdirectory.member')
		{
			$this->itemStateChange($pks, $value);
		}

		// Handle when the plugin is disabled
		if ($context == 'com_plugins.plugin' && $value === 0)
		{
			$this->pluginDisable($pks);
		}
	}

	/**
	 * Method to index an item. The item must be a FinderIndexerResult object.
	 *
	 * @param   FinderIndexerResult  $item    The item to index as an FinderIndexerResult object.
	 * @param   string               $format  The item format
	 *
	 * @return  void
	 *
	 * @since   1.7.0
	 * @throws  Exception on database error.
	 */
	protected function index (FinderIndexerResult $item, $format = 'html')
	{
		// Check if the extension is enabled
		if (JComponentHelper::isEnabled($this->extension) == false)
		{
			return;
		}

		// Initialize the item parameters.
		$registry = new Registry;
		$registry->loadString($item->params);
		$item->params = $registry;

		// Build the necessary route and path information.
		$item->url   = $this->getUrl($item->id, $this->extension, $this->layout);
		$item->route = ChurchDirectoryHelperRoute::getMemberRoute($item->slug, $item->catslug);
		$item->path  = FinderIndexerHelper::getContentPath($item->route);

		// Get the menu title if it exists.
		$title = $this->getItemMenuTitle($item->url);

		// Adjust the title if necessary.
		if (!empty($title) && $this->params->get('use_menu_title', true))
		{
			$item->title = $title;
		}

		/*
		 * Add the meta-data processing instructions based on the member
		 * configuration parameters.
		 */
		/* Handle the member position.
		 if ($item->params->get('show_position', true)) {
		    $item->addInstruction(FinderIndexer::META_CONTEXT, 'position');
		 } */
		// Handle the member street address.
		if ($item->params->get('show_street_address', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'address');
		}

		// Handle the member city.
		if ($item->params->get('show_suburb', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'city');
		}

		// Handle the member region.
		if ($item->params->get('show_state', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'region');
		}

		// Handle the member country.
		if ($item->params->get('show_country', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'country');
		}

		// Handle the member zip code.
		if ($item->params->get('show_postcode', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'zip');
		}

		// Handle the member telephone number.
		if ($item->params->get('show_telephone', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'telephone');
		}

		// Handle the member fax number.
		if ($item->params->get('show_fax', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'fax');
		}

		// Handle the member e-mail address.
		if ($item->params->get('show_email', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'email');
		}

		// Handle the member mobile number.
		if ($item->params->get('show_mobile', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'mobile');
		}

		// Handle the member webpage.
		if ($item->params->get('show_webpage', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'webpage');
		}

		// Handle the member webpage.
		if ($item->params->get('show_children', true))
		{
			$item->addInstruction(FinderIndexer::META_CONTEXT, 'children');
		}

		// Handle the member user name.
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'user');

		// Add the type taxonomy data.
		$item->addTaxonomy('Type', 'Church Member');

		// Add the category taxonomy data.
		$item->addTaxonomy('Category', $item->category, $item->cat_state, $item->cat_access);

		// Add the language taxonomy data.
		$item->addTaxonomy('Language', $item->language);

		// Add the region taxonomy data.
		if (!empty($item->region) && $this->params->get('tax_add_region', true))
		{
			$item->addTaxonomy('Region', $item->region);
		}

		// Add the country taxonomy data.
		if (!empty($item->country) && $this->params->get('tax_add_country', true))
		{
			$item->addTaxonomy('Country', $item->country);
		}

		// Get content extras.
		FinderIndexerHelper::getContentExtras($item);

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to setup the indexer to be run.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.7.0
	 */
	protected function setup ()
	{
		// Load dependent classes.
		require_once JPATH_SITE . '/components/com_churchdirectory/helpers/route.php';

		// This is a hack to get around the lack of a route helper.
		FinderIndexerHelper::getContentPath('index.php?option=com_churchdirectory');

		return true;
	}

	/**
	 * Method to get the SQL query used to retrieve the list of content items.
	 *
	 * @param   mixed  $query  A JDatabaseQuery object or null.
	 *
	 * @return  JDatabaseQuery  A database object.
	 *
	 * @since   1.7.0
	 */
	protected function getListQuery ($query = null)
	{
		$db = JFactory::getDbo();

		// Check if we can use the supplied SQL query.
		$query = $query instanceof JDatabaseQuery ? $query : $db->getQuery(true)
				->select('a.id, a.name AS title, a.alias, a.address, a.created AS start_date')
				->select('a.created_by_alias, a.modified, a.modified_by')
				->select('a.metakey, a.metadesc, a.metadata, a.language')
				->select('a.sortname1, a.sortname2, a.sortname3')
				->select('a.publish_up AS publish_start_date, a.publish_down AS publish_end_date')
				->select('a.suburb AS city, a.state AS region, a.country, a.postcode AS zip')
				->select('a.telephone, a.fax, a.misc AS summary, a.email_to AS email, a.mobile')
				->select('a.webpage, a.access, a.published AS state, a.ordering, a.params, a.catid')
				->select('c.title AS category, c.published AS cat_state, c.access AS cat_access');

		// Handle the alias CASE WHEN portion of the query
		$case_when_item_alias = ' CASE WHEN ';
		$case_when_item_alias .= $query->charLength('a.alias', '!=', '0');
		$case_when_item_alias .= ' THEN ';
		$a_id = $query->castAsChar('a.id');
		$case_when_item_alias .= $query->concatenate(array($a_id, 'a.alias'), ':');
		$case_when_item_alias .= ' ELSE ';
		$case_when_item_alias .= $a_id . ' END as slug';
		$query->select($case_when_item_alias);

		$case_when_category_alias = ' CASE WHEN ';
		$case_when_category_alias .= $query->charLength('c.alias', '!=', '0');
		$case_when_category_alias .= ' THEN ';
		$c_id = $query->castAsChar('c.id');
		$case_when_category_alias .= $query->concatenate(array($c_id, 'c.alias'), ':');
		$case_when_category_alias .= ' ELSE ';
		$case_when_category_alias .= $c_id . ' END as catslug';
		$query->select($case_when_category_alias)

				->select('u.name')
				->from('#__churchdirectory_details AS a')
				->join('LEFT', '#__categories AS c ON c.id = a.catid')
				->join('LEFT', '#__users AS u ON u.id = a.user_id');

		return $query;
	}

}
