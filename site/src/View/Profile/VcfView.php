<?php

/**
 * @package    Cwmconnect.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Site\View\Profile;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Site\Model\ProfileModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Phase 0b: vCard (.vcf) export for a single member (`view=profile&format=vcf`).
 * Streams a vCard 3.0 payload as a download. Ported from the legacy
 * Member/VcfView; the field set is kept so existing address-book imports
 * keep working.
 *
 * @since  __DEPLOY_VERSION__
 */
class VcfView extends BaseHtmlView
{
    /**
     * @param   string|null  $tpl  Unused.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app = Factory::getApplication();

        /** @var ProfileModel $model */
        $model = $this->getModel();
        $item  = $model->getItem();

        if ($item === false) {
            throw new \RuntimeException(Text::_('COM_CWMCONNECT_PROFILE_NOT_FOUND'), 404);
        }

        $title = trim((string) ($item->pc_office_role ?? '')) ?: trim((string) ($item->pc_positions ?? ''));

        // vCard body — newlines are CRLF per RFC 2426.
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' . $this->escape((string) $item->name),
            'FN:' . $this->escape((string) $item->name),
        ];

        if ($title !== '') {
            $lines[] = 'TITLE:' . $this->escape($title);
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

        $addressParts = array_filter([
            (string) ($item->address  ?? ''),
            (string) ($item->suburb   ?? ''),
            (string) ($item->state    ?? ''),
            (string) ($item->postcode ?? ''),
            (string) ($item->country  ?? ''),
        ]);

        if ($addressParts) {
            $lines[] = 'ADR;TYPE=HOME:;;' . implode(';', array_map($this->escape(...), array_pad($addressParts, 5, '')));
        }

        $lines[] = 'END:VCARD';

        $filename = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $item->name) ?: 'member';

        $app->setHeader('Content-Type', 'text/vcard; charset=UTF-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.vcf"', true);
        $app->sendHeaders();

        echo implode("\r\n", $lines) . "\r\n";

        $app->close();
    }
}
