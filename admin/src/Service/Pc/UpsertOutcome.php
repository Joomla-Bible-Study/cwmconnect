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
 * Outcome of a `MemberRepositoryInterface::upsertByPcPersonId()`
 * call. The engine reads it to bump the right counter on its
 * {@see SyncReport}, and to tell whether Smart Search needs the member again.
 *
 * - `Added`      — new row created.
 * - `Updated`    — existing row found and modified.
 * - `Unchanged`  — existing row found, holding everything Planning Center
 *                  sent already; only the time of the sync moved. The report
 *                  counts it with `Updated`.
 * - `Unarchived` — existing row found hidden (`display_in_directory = 0`);
 *                  its content is updated, but it stays hidden, as the sync
 *                  leaves visibility to administrators. Counted separately.
 *
 * @since  __DEPLOY_VERSION__
 */
enum UpsertOutcome: string
{
    case Added      = 'added';
    case Updated    = 'updated';
    case Unchanged  = 'unchanged';
    case Unarchived = 'unarchived';
}
