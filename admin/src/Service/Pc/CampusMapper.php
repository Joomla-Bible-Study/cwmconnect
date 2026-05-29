<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pc;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * K.6: map a Planning Center campus resource onto the `#__cwmconnect_dirheader`
 * column set used by the printed-directory cover page.
 *
 * Pure transform (no I/O) so it can be unit-tested in isolation, mirroring
 * {@see PersonMapper}.
 *
 * @since  __DEPLOY_VERSION__
 */
final class CampusMapper
{
    /**
     * Map one PC campus `data` element to the dirheader fields. Returns null
     * when the resource has no usable id.
     *
     * @param   array<string, mixed>  $campus  A PC campus resource.
     *
     * @return  array{pc_campus_id: int, name: string, pc_street: string, pc_city: string, pc_state: string, pc_zip: string, pc_country: string, pc_phone: string, pc_email: string, pc_website: string}|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function map(array $campus): ?array
    {
        $id = (int) ($campus['id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        $attrs = \is_array($campus['attributes'] ?? null) ? $campus['attributes'] : [];

        $str = static fn(mixed $value): string => trim((string) ($value ?? ''));

        return [
            'pc_campus_id' => $id,
            'name'         => $str($attrs['name'] ?? ''),
            'pc_street'    => $str($attrs['street'] ?? ''),
            'pc_city'      => $str($attrs['city'] ?? ''),
            'pc_state'     => $str($attrs['state'] ?? ''),
            'pc_zip'       => $str($attrs['zip'] ?? ''),
            'pc_country'   => $str($attrs['country'] ?? ''),
            'pc_phone'     => $str($attrs['phone_number'] ?? ''),
            'pc_email'     => $str($attrs['contact_email_address'] ?? ''),
            'pc_website'   => $str($attrs['website'] ?? ''),
        ];
    }
}
