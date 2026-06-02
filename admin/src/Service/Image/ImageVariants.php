<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Image;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\PhotoThumbnailer;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Generate browser-optimized web variants of a directory photo: two sizes
 * (thumb / medium) each in WebP and a JPEG fallback, so cards load a small
 * modern image instead of the full-size original. Shared by member and
 * household photos. Pure GD via {@see PhotoThumbnailer}; never throws.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ImageVariants
{
    /**
     * Variant sizes as name => [width, height] (3:4 portrait, matching the
     * directory card + PDF aspect).
     *
     * @var    array<string, array{0: int, 1: int}>
     * @since  __DEPLOY_VERSION__
     */
    public const SIZES = [
        'thumb'  => [300, 400],
        'medium' => [600, 800],
    ];

    /**
     * Output formats, best-first. WebP is served to browsers that accept it;
     * JPEG is the universal fallback.
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    public const FORMATS = ['webp', 'jpg'];

    /**
     * @param   int  $quality  Encoder quality (0–100) for both formats.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private readonly int $quality = 82) {}

    /**
     * Build every size×format variant of a source image into $destDir, named
     * `{stem}-{size}.{format}`. Best-effort: a format the GD build can't write
     * is skipped. Returns the relative filenames actually written.
     *
     * @param   string  $sourcePath  Absolute path to the source image.
     * @param   string  $destDir     Absolute directory to write variants into.
     * @param   string  $stem        Filename stem (e.g. the PC id).
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function generate(string $sourcePath, string $destDir, string $stem): array
    {
        $destDir = rtrim($destDir, '/');
        $written = [];

        foreach (self::SIZES as $size => [$width, $height]) {
            $thumbnailer = new PhotoThumbnailer($width, $height, $this->quality);

            foreach (self::FORMATS as $format) {
                $file = self::variantFilename($stem, $size, $format);

                if ($thumbnailer->generate($sourcePath, $destDir . '/' . $file, $format)) {
                    $written[] = $file;
                }
            }
        }

        return $written;
    }

    /**
     * Deterministic variant filename, shared by the writer (sync) and the
     * reader (serve proxy).
     *
     * @param   string  $stem    Filename stem.
     * @param   string  $size    One of self::SIZES keys.
     * @param   string  $format  One of self::FORMATS.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function variantFilename(string $stem, string $size, string $format): string
    {
        return $stem . '-' . $size . '.' . $format;
    }
}
