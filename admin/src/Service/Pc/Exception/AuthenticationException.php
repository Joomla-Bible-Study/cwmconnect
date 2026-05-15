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
 * Raised when PC rejects the Personal Access Token (401) or returns 403 on a
 * call that the token holder is expected to be authorised for. Distinct from
 * {@see ApiException} so the config screen can surface a "your token doesn't
 * work" message specifically (vs. generic API failure).
 *
 * @since 2.0.0
 */
class AuthenticationException extends PcException
{
}
