<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Schema\ChangeSet;

/**
 * Lightweight schema-drift check used by the cpanel banner. When this returns
 * true, the cpanel template renders a notice that links to Joomla's core
 * `com_installer&view=database` view — that page already exposes the
 * cross-extension changeset machinery + the "Fix" button. We don't need to
 * re-implement that surface inside this component.
 *
 * @since  2.0.0
 */
final class SchemaCheck
{
    /**
     * Returns true when the component's recorded schema version differs from
     * the highest version in `admin/sql/updates/mysql/`, i.e. an update is
     * pending. Returns false if everything is in sync or if the component is
     * not yet registered (fresh code, install hasn't run).
     *
     * @since  2.0.0
     */
    public static function hasFindings(): bool
    {
        $folder = JPATH_ADMINISTRATOR . '/components/com_cwmconnect/sql/updates/mysql';

        if (!is_dir($folder)) {
            return false;
        }

        $changeSet = ChangeSet::getInstance(Factory::getDbo(), $folder);

        return $changeSet->check() !== [];
    }
}
