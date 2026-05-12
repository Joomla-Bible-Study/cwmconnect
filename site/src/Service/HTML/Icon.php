<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\Service\HTML;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Connect\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Mailto\Site\Helper\MailtoHelper;
use Joomla\Registry\Registry;

/**
 * HTMLHelper "icon" service for the directory front-end.
 *
 * Registered via the component provider; templates call
 * `HTMLHelper::_('icon.email', $member, $params)` etc.
 *
 * @since  2.0.0
 */
class Icon
{
    /**
     * Render the email-popup button for a member.
     *
     * @since   2.0.0
     */
    public function email(object $member, Registry $params, array $attribs = []): string
    {
        $base = Uri::getInstance()->toString(['scheme', 'host', 'port']);
        $link = $base . Route::_(RouteHelper::getMemberRoute($member->slug, $member->catid), false);
        $url  = 'index.php?option=com_mailto&tmpl=component&link=' . MailtoHelper::addLink($link);

        $status = 'width=400,height=350,menubar=yes,resizable=yes';

        $text = $params->get('show_icons')
            ? HTMLHelper::_('image', 'system/emailButton.png', Text::_('JGLOBAL_EMAIL'), null, true)
            : '&#160;' . Text::_('JGLOBAL_EMAIL');

        $attribs['title']   = Text::_('JGLOBAL_EMAIL');
        $attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";

        return HTMLHelper::_('link', Route::_($url), $text, $attribs);
    }

    /**
     * Render the "open print view" popup link for a member.
     *
     * @since   2.0.0
     */
    public function print_popup(object $member, Registry $params, array $attribs = []): string
    {
        $url = RouteHelper::getMemberRoute($member->slug, $member->catid)
            . '&tmpl=component&print=1&layout=default';

        $status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,'
            . 'width=640,height=480,directories=no,location=no';

        $text = $params->get('show_icons')
            ? HTMLHelper::_('image', 'system/printButton.png', Text::_('JGLOBAL_PRINT'), null, true)
            : Text::_('JGLOBAL_ICON_SEP') . '&#160;' . Text::_('JGLOBAL_PRINT') . '&#160;' . Text::_('JGLOBAL_ICON_SEP');

        $attribs['title']   = Text::_('JGLOBAL_PRINT');
        $attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";
        $attribs['rel']     = 'nofollow';

        return HTMLHelper::_('link', Route::_($url), $text, $attribs);
    }

    /**
     * Render the inline "print current screen" link.
     *
     * @since   2.0.0
     */
    public function print_screen(object $member, Registry $params, array $attribs = []): string
    {
        $text = $params->get('show_icons')
            ? HTMLHelper::_('image', 'system/printButton.png', Text::_('JGLOBAL_PRINT'), null, true)
            : Text::_('JGLOBAL_ICON_SEP') . '&#160;' . Text::_('JGLOBAL_PRINT') . '&#160;' . Text::_('JGLOBAL_ICON_SEP');

        return '<a href="#" onclick="window.print();return false;">' . $text . '</a>';
    }
}