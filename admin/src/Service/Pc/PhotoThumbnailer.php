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
 * K.7: produce normalized, print-ready photo thumbnails for the directory PDF.
 *
 * mpdf cannot crop or resize images, so we do it externally with GD: every
 * member photo is centre-cropped to a fixed aspect (3:4 portrait by default)
 * and downsized to a fixed pixel box. This keeps directory cells uniform and
 * the PDF small even with hundreds of members and large source avatars.
 *
 * Pure GD (no Joomla\CMS\Image dependency) so it runs on Joomla 6 natively and
 * is unit-testable. Transparency is flattened onto white.
 *
 * @since  __DEPLOY_VERSION__
 */
final class PhotoThumbnailer
{
    /**
     * @param   int  $width    Target thumbnail width in px.
     * @param   int  $height   Target thumbnail height in px (3:4 of width).
     * @param   int  $quality  JPEG quality (0–100).
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly int $width = 300,
        private readonly int $height = 400,
        private readonly int $quality = 82,
    ) {}

    /**
     * Deterministic thumbnail filename for a member `image` value, shared by
     * the sync (writer) and the PDF (reader) so both agree on the path. PC
     * avatars (bare `{id}.ext`) keep their stem; pathed legacy images hash.
     *
     * @param   string  $imageValue  The member `image` column value.
     *
     * @return  string  Thumbnail filename (e.g. `123.jpg`), or '' when blank.
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function thumbFilename(string $imageValue): string
    {
        $imageValue = trim($imageValue);

        if ($imageValue === '') {
            return '';
        }

        if (str_contains($imageValue, '/')) {
            return sha1($imageValue) . '.jpg';
        }

        $stem = pathinfo($imageValue, \PATHINFO_FILENAME);

        return ($stem !== '' ? $stem : sha1($imageValue)) . '.jpg';
    }

    /**
     * Centre-crop + resize a source image into a JPEG thumbnail. Creates the
     * destination directory if needed. Returns true on success; never throws,
     * so a bad source just leaves the caller to fall back to a placeholder.
     *
     * @param   string  $sourcePath  Absolute path to the source image.
     * @param   string  $destPath    Absolute path to write the thumbnail.
     * @param   string  $format      Output format: 'jpg' (default) or 'webp'.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function generate(string $sourcePath, string $destPath, string $format = 'jpg'): bool
    {
        if (!is_file($sourcePath)) {
            return false;
        }

        $src = $this->load($sourcePath);

        if ($src === null) {
            return false;
        }

        try {
            $srcWidth  = imagesx($src);
            $srcHeight = imagesy($src);

            if ($srcWidth < 1 || $srcHeight < 1) {
                return false;
            }

            // Centre-crop the source to the target aspect ratio.
            $targetRatio = $this->width / $this->height;
            $sourceRatio = $srcWidth / $srcHeight;

            if ($sourceRatio > $targetRatio) {
                $cropHeight = $srcHeight;
                $cropWidth  = (int) round($srcHeight * $targetRatio);
                $cropX      = (int) round(($srcWidth - $cropWidth) / 2);
                $cropY      = 0;
            } else {
                $cropWidth  = $srcWidth;
                $cropHeight = (int) round($srcWidth / $targetRatio);
                $cropX      = 0;
                $cropY      = (int) round(($srcHeight - $cropHeight) / 2);
            }

            $dst = imagecreatetruecolor($this->width, $this->height);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $this->width, $this->height, $cropWidth, $cropHeight);

            $dir = \dirname($destPath);

            if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
                imagedestroy($dst);

                return false;
            }

            $ok = $format === 'webp' && \function_exists('imagewebp')
                ? imagewebp($dst, $destPath, $this->quality)
                : imagejpeg($dst, $destPath, $this->quality);
            imagedestroy($dst);

            return $ok && is_file($destPath);
        } finally {
            imagedestroy($src);
        }
    }

    /**
     * Generate a uniform placeholder thumbnail (light grey card with centred
     * initials) for a member with no usable photo, so every directory cell is
     * a same-size image. Uses a TrueType font when one is supplied, falling
     * back to GD's built-in font otherwise. Never throws.
     *
     * @param   string       $text      Initials to centre (e.g. "SB").
     * @param   string       $destPath  Absolute path to write the placeholder.
     * @param   string|null  $fontPath  Optional TTF path for large glyphs.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function placeholder(string $text, string $destPath, ?string $fontPath = null): bool
    {
        $text = trim($text) !== '' ? trim($text) : '?';

        $img = imagecreatetruecolor($this->width, $this->height);
        imagefill($img, 0, 0, imagecolorallocate($img, 238, 240, 242));
        $fg = imagecolorallocate($img, 154, 160, 166);

        $drawn = false;

        if ($fontPath !== null && is_file($fontPath) && \function_exists('imagettftext')) {
            $size = (int) round($this->height * 0.26);
            $bbox = imagettfbbox($size, 0, $fontPath, $text);

            if ($bbox !== false) {
                $textWidth  = $bbox[2] - $bbox[0];
                $textHeight = $bbox[1] - $bbox[7];
                $x          = (int) round(($this->width - $textWidth) / 2 - $bbox[0]);
                $y          = (int) round(($this->height - $textHeight) / 2 - $bbox[7]);
                $drawn      = imagettftext($img, $size, 0, $x, $y, $fg, $fontPath, $text) !== false;
            }
        }

        if (!$drawn) {
            $fontWidth  = imagefontwidth(5) * \strlen($text);
            $fontHeight = imagefontheight(5);
            imagestring($img, 5, (int) (($this->width - $fontWidth) / 2), (int) (($this->height - $fontHeight) / 2), $text, $fg);
        }

        $dir = \dirname($destPath);

        if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
            imagedestroy($img);

            return false;
        }

        $ok = imagejpeg($img, $destPath, $this->quality);
        imagedestroy($img);

        return $ok && is_file($destPath);
    }

    /**
     * Load a source image into a GD resource, or null for an unreadable /
     * unsupported file.
     *
     * @param   string  $path
     *
     * @return  \GdImage|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function load(string $path): ?\GdImage
    {
        $type = @exif_imagetype($path);

        $image = match ($type) {
            \IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            \IMAGETYPE_PNG  => @imagecreatefrompng($path),
            \IMAGETYPE_GIF  => @imagecreatefromgif($path),
            \IMAGETYPE_WEBP => \function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default         => false,
        };

        return $image instanceof \GdImage ? $image : null;
    }
}
