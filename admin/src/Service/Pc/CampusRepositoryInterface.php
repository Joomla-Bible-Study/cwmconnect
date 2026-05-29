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
 * K.6: persistence for Planning Center campuses, stored as
 * `#__cwmconnect_dirheader` rows keyed by `pc_campus_id`. The printed-directory
 * cover reads the primary campus from here; the campus sync writes to it.
 *
 * @since  __DEPLOY_VERSION__
 */
interface CampusRepositoryInterface
{
    /**
     * Insert or update the dirheader row for a PC campus, matched on
     * `pc_campus_id`, stamping `pc_last_synced_at`.
     *
     * @param   array{pc_campus_id: int, name: string, pc_street: string, pc_city: string, pc_state: string, pc_zip: string, pc_country: string, pc_phone: string, pc_email: string, pc_website: string}  $fields
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function upsertByPcCampusId(array $fields): void;

    /**
     * The primary campus row (lowest ordering / id) used for the cover, or
     * null when no campus has been synced.
     *
     * @return  object|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function findPrimary(): ?object;
}
