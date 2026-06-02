<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Language\Multilanguage;

/**
 * Static helper that builds front-end SEF route segments for the directory.
 *
 * @since  2.0.0
 */
abstract class RouteHelper
{
    /**
     * Build the canonical `index.php?option=…&view=member&id=…` route for a member.
     *
     * @param   int|string         $id        Member id (raw or `id:alias` slug).
     * @param   int                $catid     Owning category id.
     * @param   int|string|null    $language  Multilanguage code, or 0/null when not multi-lingual.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public static function getMemberRoute(int|string $id, int $catid = 0, int|string|null $language = 0): string
    {
        $link = 'index.php?option=com_cwmconnect&view=member&id=' . $id;

        if ($catid > 1) {
            $link .= '&catid=' . (int) $catid;
        }

        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    /**
     * Build the canonical `…&view=category&id=…` route for a category.
     *
     * @param   int|CategoryNode      $catid     Category id or node.
     * @param   int|string|null       $language  Multilanguage code.
     *
     * @return  string  Empty string when $catid resolves to 0 or less.
     *
     * @since   2.0.0
     */
    public static function getCategoryRoute(int|CategoryNode $catid, int|string|null $language = 0): string
    {
        $id = $catid instanceof CategoryNode ? (int) $catid->id : (int) $catid;

        if ($id < 1) {
            return '';
        }

        $link = 'index.php?option=com_cwmconnect&view=category&id=' . $id;

        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        return $link;
    }

    /**
     * URL to a member's photo via the gated proxy. The photo cache is blocked
     * from direct web access, so member-facing views must point <img> tags at
     * this endpoint rather than the file path.
     *
     * @param   int  $memberId
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function getPhotoRoute(int $memberId, string $size = ''): string
    {
        $url = 'index.php?option=com_cwmconnect&task=photo.serve&id=' . $memberId;

        return $size !== '' ? $url . '&size=' . $size : $url;
    }

    /**
     * `srcset` value offering the thumb (300w) and medium (600w) web variants
     * so the browser picks the right resolution; the proxy serves WebP or JPEG
     * per the request's Accept header. Pair with a `sizes` attribute.
     *
     * @param   int  $memberId
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function getPhotoSrcset(int $memberId): string
    {
        return \Joomla\CMS\Router\Route::_(self::getPhotoRoute($memberId, 'thumb')) . ' 300w, '
            . \Joomla\CMS\Router\Route::_(self::getPhotoRoute($memberId, 'medium')) . ' 600w';
    }

    /**
     * URL to a household's family photo via the gated proxy.
     *
     * @param   int     $funitId  Family-unit id.
     * @param   string  $size     Variant size (thumb / medium), or '' for full.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function getHouseholdPhotoRoute(int $funitId, string $size = ''): string
    {
        $url = 'index.php?option=com_cwmconnect&task=photo.servehousehold&id=' . $funitId;

        return $size !== '' ? $url . '&size=' . $size : $url;
    }

    /**
     * `srcset` for a household family photo (thumb 300w / medium 600w).
     *
     * @param   int  $funitId  Family-unit id.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function getHouseholdPhotoSrcset(int $funitId): string
    {
        return \Joomla\CMS\Router\Route::_(self::getHouseholdPhotoRoute($funitId, 'thumb')) . ' 300w, '
            . \Joomla\CMS\Router\Route::_(self::getHouseholdPhotoRoute($funitId, 'medium')) . ' 600w';
    }
}
