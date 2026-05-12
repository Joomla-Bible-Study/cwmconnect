<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default controller for com_cwmconnect admin. Lands on the cpanel view when
 * no explicit view/task is supplied (i.e. plain `?option=com_cwmconnect`).
 *
 * @since  2.0.0
 */
class DisplayController extends BaseController
{
    protected $default_view = 'cpanel';
}
