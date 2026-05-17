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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Phase D: AdminModel for one `#__cwmconnect_pc_field_map` row.
 *
 * @since  __DEPLOY_VERSION__
 */
class PcmappingModel extends AdminModel
{
    protected $text_prefix = 'COM_CWMCONNECT_PCMAPPING';

    public function getTable($name = 'PcFieldMap', $prefix = '', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): mixed
    {
        $form = $this->loadForm(
            'com_cwmconnect.pcmapping',
            'pcmapping',
            ['control' => 'jform', 'load_data' => $loadData],
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData(): mixed
    {
        $data = Factory::getApplication()->getUserState('com_cwmconnect.edit.pcmapping.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }
}
