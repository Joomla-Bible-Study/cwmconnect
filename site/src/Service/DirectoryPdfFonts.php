<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Service;

use Mpdf\Config\FontVariables;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The one font family the directory PDFs use, and the mPDF settings that keep them to it.
 *
 * lib_mpdf ships DejaVu Sans alone. When Composer installs mPDF,
 * libraries/mpdf/prune-fonts.php deletes its other 79 font files, which
 * takes the library from 94 MB to 8 MB. mPDF's defaults still point at some
 * of them: its default body font is `serif` (DejaVu Serif Condensed), and it
 * has its own families for `monospace` and other names. So every mPDF the
 * component builds goes through {@see config()}, which gives mPDF this one
 * family and makes it the answer to every font request. The directory
 * template already uses DejaVu Sans, so the PDFs don't change.
 *
 * DejaVu Sans covers Latin, Greek, Cyrillic, Hebrew and Arabic, but not
 * Chinese, Japanese or Korean. mPDF's font substitution is off by default,
 * so those scripts didn't print with the full font set either.
 *
 * @since  __DEPLOY_VERSION__
 */
final class DirectoryPdfFonts
{
    /**
     * mPDF's name for DejaVu Sans. prune-fonts.php keeps its four files.
     *
     * @since  __DEPLOY_VERSION__
     */
    public const string FAMILY = 'dejavusans';

    /**
     * Add the font settings to an mPDF configuration.
     *
     * They replace any font settings the caller passed, since they are what
     * keeps mPDF to the fonts lib_mpdf ships. Call this once mPDF's autoloader
     * is loaded.
     *
     * @param   array<string, mixed>  $config  The rest of the mPDF configuration.
     *
     * @return  array<string, mixed>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function config(array $config): array
    {
        // mPDF's own entry for the family, so its file names and shaping settings stay as they were.
        $family = new FontVariables()->getDefaults()['fontdata'][self::FAMILY];

        return [
            ...$config,
            'fontdata'       => [self::FAMILY => $family],
            'default_font'   => self::FAMILY,
            'sans_fonts'     => [self::FAMILY, 'sans', 'sans-serif'],
            'serif_fonts'    => [self::FAMILY, 'serif'],
            'mono_fonts'     => [self::FAMILY, 'mono', 'monospace'],
            'backupSubsFont' => [self::FAMILY],
            'backupSIPFont'  => self::FAMILY,
        ];
    }
}
