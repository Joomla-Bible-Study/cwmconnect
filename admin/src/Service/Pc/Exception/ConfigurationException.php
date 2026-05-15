<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception;

\defined('_JEXEC') or die;

/**
 * Raised when the PC client cannot be constructed because configuration is
 * missing or malformed (e.g. empty personal access token). Caught in the DI
 * factory + Phase C sync entry point to surface a "PC not configured" UI
 * state instead of a stack trace.
 *
 * @since 2.0.0
 */
class ConfigurationException extends PcException
{
}
