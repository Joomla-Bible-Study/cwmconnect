<?php

/**
 * @package    Mod_Birthdayanniversary
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Module\Birthdayanniversary\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Helper\RenderHelper;
use Joomla\Registry\Registry;

/**
 * Thin module-side helper that delegates to the component's RenderHelper for
 * the actual queries. Lets the dispatcher stay layout-focused without each
 * tmpl/ file having to know about the component namespace.
 *
 * @since  2.0.0
 */
class BirthdayanniversaryHelper
{
    /**
     * Members whose birthdate falls in the configured month.
     *
     * @return  array<int, array{name: string, id: int, day: int, access: int}>
     *
     * @since   2.0.0
     */
    public function getBirthdays(Registry $params): array
    {
        return (new RenderHelper())->getBirthdays($params);
    }

    /**
     * Family rows / single-member rows whose anniversary falls in the
     * configured month.
     *
     * @return  array<int, array{name: string, id: int, day: int, access: int}>
     *
     * @since   2.0.0
     */
    public function getAnniversary(Registry $params): array
    {
        return (new RenderHelper())->getAnniversary($params);
    }
}
