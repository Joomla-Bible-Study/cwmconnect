<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * DB helper — small utilities for the import/export pipeline.
 *
 * Legacy parity: `import()` and `export()` were always stubbed in the J3
 * source ("hold"); kept as no-ops here so callers don't break.
 *
 * @since  2.0.0
 */
class DbHelper
{
    /**
     * Stub — placeholder for an eventual member/family import routine.
     *
     * @param   array  $data  Data.
     *
     * @return  bool
     *
     * @since   2.0.0
     */
    public function import(array $data): bool
    {
        return false;
    }

    /**
     * Stub — placeholder for an eventual member/family export routine.
     *
     * @param   string  $type  Export file format.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    public function export(string $type = 'csv'): void
    {
        // Hold
    }

    /**
     * Read the configured KML row (id=1) and unpack its params Registry.
     *
     * @return  object|null  Returns null if the seed row is missing.
     *
     * @throws  \Exception
     * @since   2.0.0
     */
    public function getKmlSettings(): ?object
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__cwmconnect_kml'))
            ->where($db->quoteName('id') . ' = 1');
        $db->setQuery($query);

        $kml = $db->loadObject();

        if (!$kml) {
            return null;
        }

        $registry    = new Registry();
        $registry->loadString((string) ($kml->params ?? ''));
        $kml->params = $registry;

        return $kml;
    }
}
