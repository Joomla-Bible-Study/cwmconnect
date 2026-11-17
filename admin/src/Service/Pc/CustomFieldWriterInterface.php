<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase D: writes the custom-field values the sync maps from Planning Center.
 *
 * Exists so the sync engine can be unit-tested without a database: tests pass
 * an in-memory writer that records calls; production wires
 * {@see DatabaseCustomFieldWriter}.
 *
 * Context is always `com_cwmconnect.member`.
 *
 * @since  __DEPLOY_VERSION__
 */
interface CustomFieldWriterInterface
{
    /**
     * Make a member's mapped custom fields hold exactly the given values.
     *
     * @param   int                       $memberId  Local cwmconnect_details.id.
     * @param   array<int, list<string>>  $values    #__fields.id => the values Planning
     *                                               Center holds: one per checked option
     *                                               of a checkboxes field, none to clear it.
     *
     * @return  int  How many fields changed.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function writeValues(int $memberId, array $values): int;
}
