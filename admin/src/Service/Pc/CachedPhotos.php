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
 * The member photos the sync caches, all named by Planning Center person id:
 * the downloaded original (`<id>.<ext>`), its web copies (`web/<id>-<size>.<ext>`)
 * and its thumbnail (`thumb/<id>.jpg`). The id is matched whole, so forgetting
 * person 12 leaves person 123 alone.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class CachedPhotos
{
    /**
     * @param   string  $root  Absolute path to the photo cache directory.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private string $root) {}

    /**
     * Delete every cached file of one person. Best-effort: a file that can't
     * be deleted is left, and not counted.
     *
     * @param   int  $pcPersonId  Planning Center person id.
     *
     * @return  int  How many files were deleted.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function forget(int $pcPersonId): int
    {
        if ($pcPersonId <= 0) {
            return 0;
        }

        $root    = rtrim($this->root, '/');
        $deleted = 0;

        foreach ([$root . '/' . $pcPersonId . '.*', $root . '/web/' . $pcPersonId . '-*', $root . '/thumb/' . $pcPersonId . '.*'] as $pattern) {
            foreach (glob($pattern, \GLOB_NOSORT) ?: [] as $path) {
                if (is_file($path) && !is_link($path) && @unlink($path)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
