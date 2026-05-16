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
 * Three-state outcome of a `MemberRepositoryInterface::upsertByPcPersonId()`
 * call. The engine reads it to bump the right counter on its
 * {@see SyncReport}.
 *
 * - `Added`      — new row created.
 * - `Updated`    — existing row found and modified.
 * - `Unarchived` — existing row found that was previously archived
 *                  (`display_in_directory = 0` from a prior sweep); the row
 *                  is re-enabled AND its content updated. Counted separately
 *                  so the run summary can surface "people who came back."
 *
 * @since  __DEPLOY_VERSION__
 */
enum UpsertOutcome: string
{
    case Added      = 'added';
    case Updated    = 'updated';
    case Unarchived = 'unarchived';
}
