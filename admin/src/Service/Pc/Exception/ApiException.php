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
 * Raised for any non-auth HTTP failure from PC (4xx other than 401/403, 5xx,
 * malformed JSON, transport errors). Carries the HTTP status code so callers
 * can branch on it (e.g. retry on 5xx, log + skip on 404).
 *
 * @since 2.0.0
 */
class ApiException extends PcException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?string $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
