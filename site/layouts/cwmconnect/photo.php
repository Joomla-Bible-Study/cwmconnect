<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 *
 * Responsive, optimized member or household photo. The proxy serves WebP/JPEG
 * per the request's Accept header; we offer thumb/medium via srcset and lazy-
 * load. Falls back to a neutral avatar placeholder when there is no photo.
 *
 * @var  array  $displayData
 *   - id        int     Member id or family-unit id.
 *   - type      string  'member' (default) | 'household'.
 *   - size      string  'thumb' (default) | 'medium' — the base src.
 *   - hasPhoto  bool    Whether a real photo exists (default true).
 *   - alt       string  Alt text.
 *   - class     string  Extra <img> classes.
 *   - sizes     string  The `sizes` attribute (default card-friendly).
 *   - width     int     (default 300)  height int (default 400).
 *   - linkFull  bool    Wrap in a link to the full-resolution original.
 *   - rounded   bool    Apply rounded + object-fit-cover avatar styling.
 */

use CWM\Component\Cwmconnect\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$id       = (int) ($displayData['id'] ?? 0);
$type     = ($displayData['type'] ?? 'member') === 'household' ? 'household' : 'member';
$size     = ($displayData['size'] ?? 'thumb') === 'medium' ? 'medium' : 'thumb';
$hasPhoto = (bool) ($displayData['hasPhoto'] ?? true);
$alt      = (string) ($displayData['alt'] ?? '');
$class    = trim((string) ($displayData['class'] ?? ''));
$sizes    = (string) ($displayData['sizes'] ?? '(max-width: 600px) 50vw, 300px');
$width    = (int) ($displayData['width'] ?? 300);
$height   = (int) ($displayData['height'] ?? 400);
$linkFull = (bool) ($displayData['linkFull'] ?? false);
$rounded  = (bool) ($displayData['rounded'] ?? false);

if ($rounded) {
    $class = trim($class . ' rounded object-fit-cover');
}

if (!$hasPhoto || $id <= 0) {
    $boxStyle = $rounded ? 'width:' . $width . 'px;height:' . $width . 'px;' : 'aspect-ratio:3/4;';
    echo '<span class="cwm-photo-placeholder d-inline-flex align-items-center justify-content-center bg-body-secondary text-body-tertiary ' . htmlspecialchars($class, ENT_QUOTES) . '"'
        . ' style="' . $boxStyle . '" role="img" aria-label="' . htmlspecialchars($alt !== '' ? $alt : Text::_('COM_CWMCONNECT_IMAGE_DETAILS'), ENT_QUOTES) . '">'
        . '<span class="icon-user" aria-hidden="true"></span></span>';

    return;
}

$route  = $type === 'household' ? [RouteHelper::class, 'getHouseholdPhotoRoute'] : [RouteHelper::class, 'getPhotoRoute'];
$srcset = $type === 'household'
    ? RouteHelper::getHouseholdPhotoSrcset($id)
    : RouteHelper::getPhotoSrcset($id);

$src     = Route::_($route($id, $size));
$fullUrl = Route::_($route($id));

$img = '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '"'
    . ' srcset="' . htmlspecialchars($srcset, ENT_QUOTES) . '"'
    . ' sizes="' . htmlspecialchars($sizes, ENT_QUOTES) . '"'
    . ' width="' . $width . '" height="' . $height . '" loading="lazy" decoding="async"'
    . ($class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '')
    . ' alt="' . htmlspecialchars($alt, ENT_QUOTES) . '">';

if ($linkFull) {
    echo '<a href="' . htmlspecialchars($fullUrl, ENT_QUOTES) . '" target="_blank" rel="noopener"'
        . ' title="' . htmlspecialchars(Text::_('COM_CWMCONNECT_IMAGE_VIEW_FULL'), ENT_QUOTES) . '">' . $img . '</a>';

    return;
}

echo $img;
