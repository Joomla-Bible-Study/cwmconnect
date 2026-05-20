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

/**
 * Phase G: `view=members` paginated member list. The login wall in
 * {@see \CWM\Component\Cwmconnect\Site\Dispatcher\Dispatcher} guarantees
 * we never see this controller without an authenticated session.
 *
 * @since  __DEPLOY_VERSION__
 */
class MembersController extends BaseController
{
    protected $default_view = 'members';
}
