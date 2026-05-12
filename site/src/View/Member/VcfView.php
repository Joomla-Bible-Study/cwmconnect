<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Connect\Site\View\Member;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * vCard (.vcf) export view for a single member.
 *
 * Streams a vCard 3.0 payload with the Content-Disposition header so the
 * browser offers a download. The legacy view did the same; this port keeps
 * the field set identical so existing address-book imports keep working.
 *
 * @since  2.0.0
 */
class VcfView extends BaseHtmlView
{
    /**
     * @throws \Exception
     * @since  2.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app   = Factory::getApplication();
        $model = $this->getModel();
        $item  = $model->getItem();

        if (!$item) {
            throw new \Exception(Text::_('COM_CWMCONNECT_ERROR_MEMBER_NOT_FOUND'), 404);
        }

        $user   = $app->getIdentity();
        $groups = $user ? $user->getAuthorisedViewLevels() : [1];

        if (!\in_array($item->access, $groups, false)) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        // Build the vCard body. Newlines are CRLF per RFC 2426.
        $crlf  = "\r\n";
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' . $this->escape((string) $item->name),
            'FN:' . $this->escape((string) $item->name),
        ];

        if (!empty($item->con_position)) {
            $lines[] = 'TITLE:' . $this->escape((string) $item->con_position);
        }
        if (!empty($item->email_to)) {
            $lines[] = 'EMAIL;TYPE=INTERNET:' . $this->escape((string) $item->email_to);
        }
        if (!empty($item->telephone)) {
            $lines[] = 'TEL;TYPE=VOICE:' . $this->escape((string) $item->telephone);
        }
        if (!empty($item->mobile)) {
            $lines[] = 'TEL;TYPE=CELL:' . $this->escape((string) $item->mobile);
        }
        if (!empty($item->fax)) {
            $lines[] = 'TEL;TYPE=FAX:' . $this->escape((string) $item->fax);
        }
        if (!empty($item->webpage)) {
            $lines[] = 'URL:' . $this->escape((string) $item->webpage);
        }

        $addressParts = array_filter([
            (string) ($item->address  ?? ''),
            (string) ($item->suburb   ?? ''),
            (string) ($item->state    ?? ''),
            (string) ($item->postcode ?? ''),
            (string) ($item->country  ?? ''),
        ]);

        if ($addressParts) {
            $lines[] = 'ADR;TYPE=HOME:;;' . implode(';', array_map([$this, 'escape'], array_pad($addressParts, 5, '')));
        }

        $lines[] = 'END:VCARD';

        $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $item->name) ?: 'member';

        $app->setHeader('Content-Type', 'text/vcard; charset=UTF-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.vcf"', true);
        $app->sendHeaders();

        echo implode($crlf, $lines) . $crlf;

        $app->close();
    }
}
