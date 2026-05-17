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

/**
 * Phase F: which fields on the admin Member edit form should render
 * read-only because they're owned by Planning Center.
 *
 * Pure helper — no DB, no Joomla state. Caller passes an `stdClass`
 * (or anything `\stdClass`-shaped) representing the loaded member row;
 * we return the local-column names to lock.
 *
 * **Local columns only.** Locking PC-mapped *custom* fields lives in the
 * model where the Joomla `#__fields` table is already in scope — see
 * {@see \CWM\Component\Cwmconnect\Administrator\Service\Pc\FieldMapRepositoryInterface::lockedJoomlaFieldNames()}.
 *
 * Lock policy (per spec §6.3):
 *  - Identity / contact / address / dates → locked when `pc_person_id` is set
 *  - `image` → locked when `pc_person_id` is set (Phase E owns the avatar
 *    cache; a future Phase H photo override would bypass this lock through
 *    a different column)
 *  - `display_in_directory` → locked only when PC set it to 0 (child or
 *    `directory_status=no`). Admin can edit when PC left it visible.
 *
 * `alias`, `con_position`, `featured`, and Joomla metadata (`catid`,
 * `ordering`, `language`, ...) stay editable so admins keep control over
 * directory-local concerns.
 *
 * @since  __DEPLOY_VERSION__
 */
final class PcLockedFields
{
    /**
     * Always-locked local columns when the row is PC-linked.
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    private const BASE_LOCKED = [
        'name',
        'surname',
        'lname',
        'email_to',
        'telephone',
        'mobile',
        'address',
        'suburb',
        'state',
        'postcode',
        'country',
        'birthdate',
        'anniversary',
        'image',
    ];

    /**
     * Compute the locked-column set for a given item.
     *
     * @param   object|null  $item  Loaded member row. Pass `null` when the
     *                               form is rendered for a brand-new record
     *                               (id=0); nothing is ever locked there.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function forItem(?object $item): array
    {
        if ($item === null) {
            return [];
        }

        $pcPersonId = (int) ($item->pc_person_id ?? 0);

        if ($pcPersonId <= 0) {
            return [];
        }

        $locked = self::BASE_LOCKED;

        // PC set this row's visibility to 0 (either via the child boolean
        // or directory_status=no). Admin can't accidentally re-enable
        // visibility without unlinking the row first.
        if ((int) ($item->display_in_directory ?? 1) === 0) {
            $locked[] = 'display_in_directory';
        }

        return $locked;
    }
}
