<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default display controller. Used by every view that doesn't ship its own
 * controller (categories, category, directory, featured) — they just need the
 * dispatcher to land on `display`.
 *
 * @since  2.0.0
 */
class DisplayController extends BaseController
{
    /** @var string Fallback view when none is provided. */
    protected $default_view = 'directory';
}
