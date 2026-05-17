<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Home view controller — landing page that renders the featured member
 * gallery for the directory.
 *
 * @since  2.0.0
 */
class HomeController extends BaseController
{
    /**
     * Always pass `ignore_request` so the list model isn't fed user input from
     * the request — the home page only ever shows featured members.
     *
     * @param   string                $name    Model name.
     * @param   string                $prefix  Class prefix (unused with PSR-4 MVCFactory).
     * @param   array<string, mixed>  $config  Extra configuration.
     *
     * @return  BaseDatabaseModel|false
     *
     * @since   2.0.0
     */
    public function getModel($name = 'Home', $prefix = '', $config = [])
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true] + $config);
    }
}
