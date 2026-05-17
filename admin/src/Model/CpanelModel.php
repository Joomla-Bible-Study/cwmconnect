<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Methods to back the control panel view.
 *
 * Currently a placeholder model — the cpanel view does not require data, but
 * Joomla's MVC factory expects a model class to resolve.
 *
 * @since  2.0.0
 */
class CpanelModel extends BaseDatabaseModel {}
