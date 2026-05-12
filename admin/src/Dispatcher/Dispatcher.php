<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * Component dispatcher for com_cwmconnect admin. Kept as a PSR-4 hook
 * point for future dispatch-time concerns (ACL preflight, request
 * rewriting). The default landing view (cpanel) is selected by
 * DisplayController's $default_view — ComponentDispatcher hardcodes
 * "display" as the fallback controller name, so an override property
 * here would be dead code.
 *
 * @since  2.0.0
 */
class Dispatcher extends ComponentDispatcher
{
}
