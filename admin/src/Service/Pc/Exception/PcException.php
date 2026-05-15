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
 * Base exception for the Planning Center sync client. All other Pc\Exception
 * classes extend this so callers can `catch (PcException $e)` to handle any
 * PC failure mode without enumerating subclasses.
 *
 * @since 2.0.0
 */
class PcException extends \RuntimeException
{
}