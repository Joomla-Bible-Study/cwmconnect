<?php

/**
 * Delete every mPDF font except DejaVu Sans. Composer runs this here after it installs or updates mPDF.
 *
 * mPDF ships 83 font files, 87 MB in all. The directory PDFs use one family,
 * DejaVu Sans, and DirectoryPdfFonts::config() (site/src/Service) keeps mPDF to
 * it, so lib_mpdf ships 8 MB instead of 94. The build and CI both run Composer
 * here, so the unit tests use the same fonts a site gets.
 *
 * To add a font, list its files in KEEP and add the family to DirectoryPdfFonts.
 *
 * Usage: php prune-fonts.php [font folder]   (defaults to mPDF's ttfonts folder)
 *
 * @package    Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

// A dev site links this whole folder in, so refuse to run from the web.
if (PHP_SAPI !== 'cli') {
    exit(1);
}

// DejaVu Sans in its four styles, mPDF's 'dejavusans' family, plus the font licence.
const KEEP = ['DejaVuSans.ttf', 'DejaVuSans-Bold.ttf', 'DejaVuSans-Oblique.ttf', 'DejaVuSans-BoldOblique.ttf', 'DejaVuinfo.txt'];

$dir = $argv[1] ?? __DIR__ . '/vendor/mpdf/mpdf/ttfonts';

if (!is_dir($dir)) {
    fwrite(STDERR, "prune-fonts: no font folder at {$dir}\n");
    exit(1);
}

$removed = 0;

foreach (scandir($dir) ?: [] as $name) {
    $path = $dir . '/' . $name;

    if (\in_array($name, KEEP, true) || !(is_file($path) || is_link($path))) {
        continue;
    }

    if (!unlink($path)) {
        fwrite(STDERR, "prune-fonts: could not delete {$path}\n");
        exit(1);
    }

    $removed++;
}

// A kept file that is missing means mPDF renamed or dropped it: the PDFs would fail to render.
$missing = array_filter(KEEP, static fn (string $name): bool => !is_file($dir . '/' . $name));

if ($missing !== []) {
    fwrite(STDERR, 'prune-fonts: mPDF no longer ships ' . implode(', ', $missing) . "; update KEEP here and DirectoryPdfFonts\n");
    exit(1);
}

echo "prune-fonts: deleted {$removed} files, kept DejaVu Sans\n";
