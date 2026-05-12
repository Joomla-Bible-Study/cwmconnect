<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Schema\ChangeSet;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

/**
 * Database management model — drives the schema-changeset machinery for
 * the component's own tables and reconciles the version recorded in
 * `#__schemas` / `#__extensions` against the manifest.
 *
 * @since  2.0.0
 */
class DatabaseModel extends BaseDatabaseModel
{
    /**
     * Apply any pending schema changes and reconcile recorded versions.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function fix(): void
    {
        $changeSet = $this->getChangeSet();
        $changeSet->fix();

        $this->fixSchemaVersion($changeSet);
        $this->fixUpdateVersion();
        $this->fixDefaultTextFilters();
    }

    /**
     * Build a ChangeSet over the component's update folder.
     *
     * @return  ChangeSet
     *
     * @since   2.0.0
     */
    public function getChangeSet(): ChangeSet
    {
        $folder = JPATH_ADMINISTRATOR . '/components/com_churchdirectory/sql/updates/mysql';

        return ChangeSet::getInstance($this->getDatabase(), $folder);
    }

    /**
     * Get the currently-recorded schema version for the component.
     *
     * @return  string|null
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getSchemaVersion(): ?string
    {
        $extensionId = $this->getExtensionId();

        if ($extensionId === 0) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('version_id'))
            ->from($db->quoteName('#__schemas'))
            ->where($db->quoteName('extension_id') . ' = ' . (int) $extensionId);
        $db->setQuery($query);

        $version = $db->loadResult();

        return $version === null ? null : (string) $version;
    }

    /**
     * Reconcile `#__schemas` against the highest applied changeset.
     *
     * @param   ChangeSet  $changeSet  The changeset to reconcile against.
     *
     * @return  string|false  The recorded version on success, or false on insert failure.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function fixSchemaVersion(ChangeSet $changeSet): string|false
    {
        $schema      = (string) $changeSet->getSchema();
        $db          = $this->getDatabase();
        $extensionId = $this->getExtensionId();

        if ($extensionId === 0) {
            return false;
        }

        $current = $this->getSchemaVersion();

        if ($current === $schema) {
            return $current;
        }

        $delete = $db->getQuery(true)
            ->delete($db->quoteName('#__schemas'))
            ->where($db->quoteName('extension_id') . ' = ' . (int) $extensionId);
        $db->setQuery($delete)->execute();

        $insert = $db->getQuery(true)
            ->insert($db->quoteName('#__schemas'))
            ->set($db->quoteName('extension_id') . ' = ' . (int) $extensionId)
            ->set($db->quoteName('version_id') . ' = ' . $db->quote($schema));
        $db->setQuery($insert);

        return $db->execute() ? $schema : false;
    }

    /**
     * Read the version recorded in the `#__extensions` manifest cache.
     *
     * @return  string|null
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getUpdateVersion(): ?string
    {
        $table = Table::getInstance('Extension');
        $extId = $this->getExtensionId();

        if ($extId === 0 || !$table->load($extId)) {
            return null;
        }

        $cache = new Registry($table->manifest_cache);

        return $cache->get('version');
    }

    /**
     * Force the manifest_cache version to match the live manifest.
     *
     * @return  string|false
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function fixUpdateVersion(): string|false
    {
        $table = Table::getInstance('Extension');
        $extId = $this->getExtensionId();

        if ($extId === 0 || !$table->load($extId)) {
            return false;
        }

        $cache         = new Registry($table->manifest_cache);
        $current       = (string) $cache->get('version');
        $manifest      = $this->getCompVersion();

        if ($current === $manifest) {
            return $current;
        }

        $cache->set('version', $manifest);
        $table->manifest_cache = $cache->toString();

        return $table->store() ? $manifest : false;
    }

    /**
     * Get the current component params (text filters and otherwise).
     *
     * @return  string|null
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getDefaultTextFilters(): ?string
    {
        $table = Table::getInstance('Extension');
        $extId = $table->find(['name' => 'com_churchdirectory']);

        if (!$extId || !$table->load($extId)) {
            return null;
        }

        return (string) $table->params;
    }

    /**
     * Backfill the #__extensions row's params from the component manifest's
     * filter defaults if the stored params are blank.
     *
     * @return  bool
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function fixDefaultTextFilters(): bool
    {
        $table = Table::getInstance('Extension');
        $extId = $table->find(['name' => 'com_churchdirectory']);

        if (!$extId || !$table->load($extId)) {
            return false;
        }

        if ($table->params) {
            return false;
        }

        // With $table->params empty, ComponentHelper falls back to the
        // manifest's <config> defaults — that's where the filter set lives.
        $manifestParams = ComponentHelper::getParams('com_churchdirectory');

        if ($manifestParams->get('filters')) {
            $newParams = new Registry();
            $newParams->set('filters', $manifestParams->get('filters'));
            $table->params = (string) $newParams;

            return (bool) $table->store();
        }

        return false;
    }

    /**
     * Resolve the component's `extension_id`.
     *
     * @return  int  Zero when the component is not registered (e.g. dev run).
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getExtensionId(): int
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_churchdirectory'));
        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Read the version from the component manifest.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function getCompVersion(): string
    {
        $manifest = JPATH_ADMINISTRATOR . '/components/com_churchdirectory/churchdirectory.xml';

        if (!is_file($manifest)) {
            return '';
        }

        $xml = simplexml_load_file($manifest);

        if (!$xml instanceof \SimpleXMLElement) {
            return '';
        }

        return (string) $xml->version;
    }
}
