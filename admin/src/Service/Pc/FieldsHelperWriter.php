<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Component\Fields\Administrator\Model\FieldModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Production `CustomFieldWriterInterface` backed by com_fields' admin
 * `FieldModel::setFieldValue()`.
 *
 * Joomla doesn't expose a static "set field value" helper — the API lives
 * on the admin `FieldModel`, which we instantiate via com_fields' own
 * MVCFactory so the bound DB / cache / dispatcher come along for free.
 * The component context is fixed to `com_cwmconnect.member` because that's
 * the only context our sync writes into.
 *
 * Note: `FieldModel::setFieldValue()` calls
 * `FieldsHelper::canEditFieldValue()` which checks
 * `Factory::getUser()->authorise('core.edit.value', ...)`. The Joomla
 * scheduler / CLI runs the task under a user identity, and the manual
 * "Sync now" Cpanel button is gated by the component's own admin ACL.
 *
 * @since  __DEPLOY_VERSION__
 */
final class FieldsHelperWriter implements CustomFieldWriterInterface
{
    public function setFieldValue(int $memberId, int $joomlaFieldId, string $value): bool
    {
        $model = ComponentHelper::getComponent('com_fields')
            ->getMVCFactory()
            ->createModel('Field', 'Administrator', ['ignore_request' => true]);

        if (!$model instanceof FieldModel) {
            return false;
        }

        return (bool) $model->setFieldValue((string) $joomlaFieldId, (string) $memberId, $value);
    }
}
