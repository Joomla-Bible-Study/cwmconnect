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
}