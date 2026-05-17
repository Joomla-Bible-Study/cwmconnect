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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Phase D: list controller for PC ↔ Joomla custom-field mappings.
 *
 * @since  __DEPLOY_VERSION__
 */
class PcmappingsController extends AdminController
{
    protected $text_prefix = 'COM_CWMCONNECT_PCMAPPINGS';

    public function getModel(
        $name = 'Pcmapping',
        $prefix = 'Administrator',
        $config = ['ignore_request' => true],
    ): BaseDatabaseModel {
        return parent::getModel($name, $prefix, $config);
    }
}
