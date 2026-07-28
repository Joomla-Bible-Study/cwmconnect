<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Redeem;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\AccessCode\AccessCodeService;
use CWM\Component\Cwmconnect\Site\Helper\MemberAccess;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * The access-code form: where a registered user who is not yet a member is
 * sent, instead of being shown a bare "access denied".
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Whether an admin has configured a usable code.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    protected bool $enabled = false;

    /**
     * Whether the viewer is signed in.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    protected bool $loggedIn = false;

    /**
     * Whether the viewer already holds directory access.
     *
     * @var    bool
     * @since  __DEPLOY_VERSION__
     */
    protected bool $alreadyIn = false;

    /**
     * @param   string|null  $tpl  Template name.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $user = Factory::getApplication()->getIdentity();

        $this->loggedIn  = (int) ($user?->id ?? 0) > 0;
        $this->alreadyIn = MemberAccess::userHas($user);
        $this->enabled   = new AccessCodeService(ComponentHelper::getParams('com_cwmconnect'))->isEnabled();

        parent::display($tpl);
    }
}
