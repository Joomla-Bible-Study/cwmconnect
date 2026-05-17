<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Phase D: indirection over `Joomla\Component\Fields\Administrator\Helper\FieldsHelper::setFieldValue()`.
 *
 * Exists so the sync engine can be unit-tested without booting com_fields:
 * tests pass an in-memory writer that records calls; production wires
 * {@see FieldsHelperWriter} which delegates straight to FieldsHelper.
 *
 * Context is always `com_cwmconnect.member`; member id + field id +
 * stringified value travel together because that's the FieldsHelper API
 * shape.
 *
 * @since  __DEPLOY_VERSION__
 */
interface CustomFieldWriterInterface
{
    /**
     * Persist one custom-field value against a member row.
     *
     * @param   int     $memberId        Local cwmconnect_details.id.
     * @param   int     $joomlaFieldId   #__fields.id (com_cwmconnect.member).
     * @param   string  $value           Value to store. The caller has already
     *                                    stringified PC's native datum.
     *
     * @return  bool   True on a successful write.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function setFieldValue(int $memberId, int $joomlaFieldId, string $value): bool;
}
