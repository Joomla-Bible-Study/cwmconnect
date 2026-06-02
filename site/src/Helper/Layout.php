<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Helper;

use Joomla\CMS\Layout\LayoutHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Thin wrapper for the component's shared front-end layout partials under
 * `components/com_cwmconnect/layouts/cwmconnect/`. Lets every template compose
 * the same Bootstrap 5 building blocks (photo, member card, section card,
 * page header, empty state) without repeating the LayoutHelper base path.
 *
 * @since  __DEPLOY_VERSION__
 */
final class Layout
{
    /**
     * Render a `cwmconnect.*` layout partial.
     *
     * @param   string                $name  Partial name (e.g. 'photo', 'membercard').
     * @param   array<string, mixed>  $data  Layout data.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function render(string $name, array $data = []): string
    {
        return LayoutHelper::render(
            'cwmconnect.' . $name,
            $data,
            JPATH_ROOT . '/components/com_cwmconnect/layouts',
        );
    }
}
