<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Field\CheckboxesField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;

/**
 * Custom form field that fetches membership types from the Planning Center
 * API and renders them as checkboxes. Falls back to a static list if the
 * API is unreachable or PC is not configured.
 *
 * @since  __DEPLOY_VERSION__
 */
class PcMembershipStatusField extends CheckboxesField
{
    /** @since __DEPLOY_VERSION__ */
    protected $type = 'PcMembershipStatus';

    /**
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function getOptions(): array
    {
        $options  = [];
        $statuses = $this->fetchFromPc();

        foreach ($statuses as $status) {
            $options[] = HTMLHelper::_('select.option', $status, $status);
        }

        return array_merge(parent::getOptions(), $options);
    }

    /**
     * Fetch membership type values from the PC API.
     *
     * @return  list<string>
     *
     * @since   __DEPLOY_VERSION__
     */
    private function fetchFromPc(): array
    {
        $params = ComponentHelper::getParams('com_cwmconnect');
        $appId  = (string) $params->get('pc_application_id', '');
        $secret = (string) $params->get('pc_personal_access_token', '');

        if ($appId === '' || $secret === '') {
            return self::FALLBACK;
        }

        try {
            $http     = HttpFactory::getHttp();
            $headers  = [
                'Authorization'     => 'Basic ' . base64_encode($appId . ':' . $secret),
                'Accept'            => 'application/json',
                'X-PCO-API-Version' => '2025-11-10',
            ];

            $response = $http->get(
                'https://api.planningcenteronline.com/people/v2/membership_types',
                $headers,
                10,
            );

            if ((int) $response->code !== 200) {
                return self::FALLBACK;
            }

            $decoded = json_decode($response->body, true);
            $values  = [];

            foreach ($decoded['data'] ?? [] as $item) {
                $value = (string) ($item['attributes']['value'] ?? '');

                if ($value !== '') {
                    $values[] = $value;
                }
            }

            return $values !== [] ? $values : self::FALLBACK;
        } catch (\Throwable) {
            return self::FALLBACK;
        }
    }

    /**
     * Static fallback when PC is not configured or unreachable.
     *
     * @var    list<string>
     * @since  __DEPLOY_VERSION__
     */
    private const FALLBACK = [
        'Member',
        'Regular Attender',
        'Visitor',
        'Participant',
    ];
}
