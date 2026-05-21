<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pairing;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Identity-binding heuristic for the Phase H pairing triggers. Implements
 * spec §8.2 — all four triggers (PC sync, admin manual pair, onUserAfterSave,
 * locally-created member with email) share this single email-match contract
 * so behaviour is identical regardless of which side fires first.
 *
 * The interface exists so the engine and the user plugin can both depend on
 * a pure contract, and so tests can swap in an in-memory implementation
 * without a real DB.
 *
 * @since  __DEPLOY_VERSION__
 */
interface MemberPairingInterface
{
    /**
     * Look up the single unpaired member row that matches the given email.
     *
     * "Unpaired" tolerates both the pre-H.1 sentinel (`user_id = 0`) and the
     * post-H.1 nullable column (`user_id IS NULL`) so this method works on
     * either schema without branching.
     *
     * Returns null on zero matches AND on multiple matches — ambiguity is
     * not resolved automatically; the spec defers conflict resolution to
     * the admin (§8.2). Trashed rows (`published = -2`) are excluded.
     *
     * @param   string  $email  Email address to match against `email_to`.
     *                          Case-insensitive (utf8mb4_unicode_ci).
     *
     * @return  int|null  Member row id, or null on no-match / ambiguous.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function findUnpairedMemberIdByEmail(string $email): ?int;

    /**
     * Look up the single Joomla user that matches the given email.
     *
     * Blocked accounts (`#__users.block = 1`) are excluded — pairing a
     * member to a blocked user defeats the portal's purpose. Returns null
     * on zero matches AND on multiple matches.
     *
     * @param   string  $email  Email to match against `#__users.email`.
     *
     * @return  int|null  Joomla user id, or null on no-match / ambiguous.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function findJoomlaUserIdByEmail(string $email): ?int;

    /**
     * Bind a Joomla user id to a member row, *only* if the row is currently
     * unpaired. Returns true when the UPDATE affected one row (pair
     * succeeded), false when the row was already paired or did not exist
     * (no silent overwrite — admin must unlink first, per spec §8.2).
     *
     * @param   int  $memberId  `#__cwmconnect_details.id`
     * @param   int  $userId    `#__users.id`
     *
     * @return  bool  True if the row was paired by this call.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function pairMemberToUser(int $memberId, int $userId): bool;
}
