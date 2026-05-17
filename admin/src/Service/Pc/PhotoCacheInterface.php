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
 * Phase E: cache a PC person's avatar locally and return the relative path
 * plus the hash the sync engine should persist on the member row.
 *
 * Returns one of three outcomes:
 *  - {@see PhotoCacheResult} when a new download landed (or the existing
 *    file was kept because the hash already matched).
 *  - null when there's no avatar to cache (PC returned no `avatar`, or only
 *    a generic `demographic_avatar_url` placeholder we deliberately skip).
 *
 * Decision matrix (per spec §5.3 step 4):
 *  | currentHash | avatarUrl       | expected result          |
 *  | ----------- | --------------- | ------------------------ |
 *  | null        | null            | null (no-op)             |
 *  | non-null    | null            | null (keep old file)     |
 *  | null        | new url         | Downloaded result        |
 *  | matching    | same url        | Unchanged result         |
 *  | mismatch    | new url         | Downloaded result        |
 *
 * @since  __DEPLOY_VERSION__
 */
interface PhotoCacheInterface
{
    /**
     * Ensure the local cache reflects the given PC avatar.
     *
     * @param   int          $pcPersonId   PC person id (filename stem).
     * @param   string|null  $avatarUrl    PC `avatar` URL, or null when the
     *                                      person has no real photo.
     * @param   string|null  $currentHash  The image_hash we last stored, or
     *                                      null if the row has no cached
     *                                      image yet.
     *
     * @return  PhotoCacheResult|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function cache(int $pcPersonId, ?string $avatarUrl, ?string $currentHash): ?PhotoCacheResult;
}
