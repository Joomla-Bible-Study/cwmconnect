<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Search;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Tells Smart Search about member rows written outside the admin form, whose
 * saves it already hears: by the Planning Center sync, the My Profile portal,
 * Reconcile and a privacy removal.
 *
 * @since  __DEPLOY_VERSION__
 */
interface SearchIndexInterface
{
    /**
     * Index a member again, as it now is; a member hidden from the directory
     * is taken out instead.
     *
     * @param   int  $memberId  Member row id.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function memberChanged(int $memberId): void;

    /**
     * Take members out of the index.
     *
     * @param   list<int>  $memberIds  Member row ids.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function membersRemoved(array $memberIds): void;
}
