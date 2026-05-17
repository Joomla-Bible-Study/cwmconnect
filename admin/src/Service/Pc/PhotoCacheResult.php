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
 * Phase E: outcome envelope for {@see PhotoCacheInterface::cache()}.
 *
 * `relativePath` is the path under `media/com_cwmconnect/photos/` we want
 * stored in `#__cwmconnect_details.image`. `hash` is the SHA-256 of the
 * source URL — written to `image_hash` so the next sync can short-circuit
 * when the URL hasn't changed. `downloaded` distinguishes "we just wrote
 * bytes" from "we kept the existing file" so the SyncReport counters can
 * separate fresh downloads from no-op hits.
 *
 * @since  __DEPLOY_VERSION__
 */
final class PhotoCacheResult
{
    public function __construct(
        public readonly string $relativePath,
        public readonly string $hash,
        public readonly bool $downloaded,
    ) {}
}
