<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Control panel controller.
 *
 * @since  2.0.0
 */
class CpanelController extends BaseController
{
    /**
     * Default view for the control panel.
     *
     * @var string
     * @since 2.0.0
     */
    protected $default_view = 'cpanel';

    /**
     * Display the control panel view.
     *
     * @param   bool   $cachable   If true, the view output will be cached.
     * @param   array  $urlparams  An array of safe URL parameters.
     *
     * @return  static  This object to support chaining.
     *
     * @since   2.0.0
     */
    public function display($cachable = false, $urlparams = []): static
    {
        $this->input->set('view', 'cpanel');

        return parent::display($cachable, $urlparams);
    }
}
