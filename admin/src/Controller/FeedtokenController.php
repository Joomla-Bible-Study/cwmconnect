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

use Joomla\CMS\MVC\Controller\FormController;

/**
 * Feed token single-item form controller.
 *
 * @since  __DEPLOY_VERSION__
 */
class FeedtokenController extends FormController
{
    /**
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $view_list = 'feedtokens';
}
