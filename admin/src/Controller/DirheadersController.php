<?php

/**
 * @package    Churchdirectory.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Churchdirectory\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Dirheaders list controller.
 *
 * @since  2.0.0
 */
class DirheadersController extends AdminController
{
    /**
     * @var string
     * @since 2.0.0
     */
    protected $text_prefix = 'COM_CHURCHDIRECTORY_DIRHEADERS';

    /**
     * Proxy for getModel.
     *
     * @param   string  $name     The model name.
     * @param   string  $prefix   The class prefix.
     * @param   array   $config   Configuration array.
     *
     * @return  BaseDatabaseModel
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Dirheader', $prefix = 'Administrator', $config = ['ignore_request' => true]): BaseDatabaseModel
    {
        return parent::getModel($name, $prefix, $config);
    }
}
