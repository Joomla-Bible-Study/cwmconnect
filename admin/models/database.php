<?php
/**
 * @package    ChurchDirectory.Admin
 * @copyright  (C) 2007 - 2011 Joomla Bible Study Team All rights reserved
 * @license    http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link       http://www.JoomlaBibleStudy.org
 * @since      1.7.2
 * */

defined('_JEXEC') or die;

// Import library dependencies
JLoader::register('InstallerModel', JPATH_ADMINISTRATOR . '/components/com_installer/models/extension.php');
JLoader::register('Com_ChurchDirectoryInstallerScript', JPATH_COMPONENT_ADMINISTRATOR . 'file.script.php');

/**
 * Database Manage Model
 *
 * @package  ChurchDirectory.Admin
 * @since    1.7.2
 */
class ChurchDirectoryModelDatabase extends InstallerModel
{

	/**
	 * Context of model
	 *
	 * @var string
	 */
	protected $_context = 'com_churchdirectory.discover';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Ordering
	 * @param   string  $direction  Direction of the list
	 *
	 * @since    1.7.2
	 * @return void
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();
		$this->setState('message', $app->getUserState('com_churchdirectory.message'));
		$this->setState('extension_message', $app->getUserState('com_churchdirectory.extension_message'));
		$app->setUserState('com_churchdirectory.message', '');
		$app->setUserState('com_churchdirectory.extension_message', '');
		parent::populateState('name', 'asc');
	}

	/**
	 * Fixes database problems
	 *
	 * @return void
	 */
	public function fix()
	{
		$changeSet = $this->getItems();
		$changeSet->fix();
		$this->fixSchemaVersion($changeSet);
		$this->fixUpdateVersion();
		$this->fixDefaultTextFilters();
	}

	/**
	 * Gets the changeset object
	 *
	 * @return  JSchemaChangeset
	 */
	public function getItems()
	{
		$folder    = JPATH_COMPONENT_ADMINISTRATOR . '/sql/updates/';
		$changeSet = JSchemaChangeset::getInstance(JFactory::getDbo(), $folder);

		return $changeSet;
	}

	/**
	 * Overrides Pagination
	 *
	 * @return boolean
	 */
	public function getPagination()
	{
		return true;
	}

	/**
	 * Get version from #__schemas table
	 *
	 * @return  mixed  the return value from the query, or null if the query fails
	 *
	 * @throws Exception
	 */
	public function getSchemaVersion()
	{
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$extensionresult = $this->getExtentionId();
		$query->select('version_id')->from($db->qn('#__schemas'))
			->where('extension_id = "' . $extensionresult . '"');
		$db->setQuery($query);
		$result = $db->loadResult();

		return $result;
	}

	/**
	 * Fix schema version if wrong
	 *
	 * @param   JSchemaChangeSet  $changeSet  ??
	 *
	 * @return   mixed  string schema version if success, false if fail
	 */
	public function fixSchemaVersion($changeSet)
	{
		// Get correct schema version -- last file in array
		$schema          = $changeSet->getSchema();
		$db              = JFactory::getDbo();
		$result          = false;
		$extensionresult = $this->getExtentionId();

		// Check value. If ok, don't do update
		$version = $this->getSchemaVersion();

		if ($version == $schema)
		{
			$result = $version;
		}
		else
		{
			// Delete old row
			$query = $db->getQuery(true);
			$query->delete($db->qn('#__schemas'));
			$query->where($db->qn('extension_id') . ' = "' . $extensionresult . '"');
			$db->setQuery($query);
			$db->execute();

			// Add new row
			$query = $db->getQuery(true);
			$query->insert($db->qn('#__schemas'));
			$query->set($db->qn('extension_id') . '= "' . $extensionresult . '"');
			$query->set($db->qn('version_id') . '= ' . $db->q($schema));
			$db->setQuery($query);

			if ($db->execute())
			{
				$result = $schema;
			}
		}

		return $result;
	}

	/**
	 * Get current version from #__extensions table
	 *
	 * @return  mixed   version if successful, false if fail
	 */
	public function getUpdateVersion()
	{
		$table = JTable::getInstance('Extension');
		$table->load($this->getExtentionId());
		$cache = new JRegistry($table->manifest_cache);

		return $cache->get('version');
	}

	/**
	 * Fix Joomla version in #__extensions table if wrong (doesn't equal JVersion short version)
	 *
	 * @return   mixed  string update version if success, false if fail
	 */
	public function fixUpdateVersion()
	{
		$table = JTable::getInstance('Extension');
		$table->load($this->getExtentionId());
		$cache         = new JRegistry($table->manifest_cache);
		$updateVersion = $cache->get('version');

		if ($updateVersion == $this->getCompVersion())
		{
			return $updateVersion;
		}
		else
		{
			$cache->set('version', $this->getCompVersion());
			$table->manifest_cache = $cache->toString();

			if ($table->store())
			{
				return $this->getCompVersion();
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Check if com_churchdirectory parameters are blank.
	 *
	 * @return  string  default text filters (if any)
	 */
	public function getDefaultTextFilters()
	{
		$table = JTable::getInstance('Extension');
		$table->load($table->find(array('name' => 'com_churchdirectory')));

		return $table->params;
	}

	/**
	 * Check if com_churchdirectory parameters are blank. If so, populate with com_content text filters.
	 *
	 * @return  mixed  boolean true if params are updated, null otherwise
	 */
	public function fixDefaultTextFilters()
	{
		$table = JTable::getInstance('Extension');
		$table->load($table->find(array('name' => 'com_churchdirectory')));

		// Check for empty $config and non-empty content filters
		if (!$table->params)
		{
			// Get filters from com_content and store if you find them
			$contentParams = JComponentHelper::getParams('com_churchdirectory');

			if ($contentParams->get('filters'))
			{
				$newParams = new JRegistry;
				$newParams->set('filters', $contentParams->get('filters'));
				$table->params = (string) $newParams;
				$table->store();

				return true;
			}
		}

		return false;
	}

	/**
	 * To retreave component extention_id
	 *
	 * @return int
	 *
	 * @since 7.1.0
	 * @throws Exception
	 */
	public function getExtentionId()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('extension_id')->from($db->qn('#__extensions'))
			->where('element = "com_churchdirectory"');
		$db->setQuery($query);
		$result = $db->loadResult();

		return $result;
	}

	/**
	 * To retreave component version
	 *
	 * @return string Version of component
	 *
	 * @since 1.7.3
	 */
	public function getCompVersion()
	{
		$file     = JPATH_COMPONENT_ADMINISTRATOR . '/churchdirectory.xml';
		/** @var object $xml */
		$xml      = simplexml_load_file($file, 'JXMLElement');
		$jversion = (string) $xml->version;

		return $jversion;
	}

}
