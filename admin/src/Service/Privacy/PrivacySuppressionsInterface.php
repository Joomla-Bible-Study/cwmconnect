<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Privacy;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Planning Center people removed under a privacy request, whom the sync must
 * neither refill nor re-import until an administrator restores them.
 *
 * A removal suppresses the person and leaves their blanked row archived.
 * Taking that row out of the archive releases them, and the next sync refills
 * the row, lists them again and forgets the suppression. Archiving the row
 * again before that sync takes the release back.
 *
 * @since  __DEPLOY_VERSION__
 */
interface PrivacySuppressionsInterface
{
    /**
     * Whether a person is suppressed, read fresh, so that a removal made
     * while a sync runs is seen before the sync writes them.
     *
     * @param   int  $pcPersonId  Planning Center person id.
     *
     * @return  bool|null  Null when not suppressed; otherwise whether an
     *                     administrator has released them.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function stateOf(int $pcPersonId): ?bool;

    /**
     * Suppress a person, or suppress them again after a release. Suppressing
     * someone already suppressed keeps the date they were first removed.
     *
     * @param   int  $pcPersonId  Planning Center person id.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function suppress(int $pcPersonId): void;

    /**
     * Release the suppressed people behind these member rows, for the next
     * sync to restore.
     *
     * @param   list<int>  $memberIds  Member row ids.
     *
     * @return  int  How many people were released.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function releaseMembers(array $memberIds): int;

    /**
     * Take back the release of the people behind these member rows.
     *
     * @param   list<int>  $memberIds  Member row ids.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function unreleaseMembers(array $memberIds): void;

    /**
     * Make sure a suppressed person is still removed: erase their row again,
     * which clears anything a sync running alongside the removal wrote back,
     * or recreate the blank, archived row an administrator restores them
     * from if it was deleted.
     *
     * @param   int  $pcPersonId  Planning Center person id.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function ensureRemoved(int $pcPersonId): void;

    /**
     * Finish restoring a released person once the sync has refilled their
     * row: list them in the directory again and forget the suppression.
     *
     * @param   int  $pcPersonId  Planning Center person id.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function completeRestore(int $pcPersonId): void;
}
