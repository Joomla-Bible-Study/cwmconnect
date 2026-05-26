<?php

/**
 * @package    Plg_Privacy_Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Plugin\Privacy\Cwmconnect\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

/**
 * Phase L: GDPR privacy plugin for CWM Connect member data.
 *
 * Handles two privacy events:
 *  - **Export**: collects all member data linked to the requesting user
 *    (via user_id FK) and any feed tokens they own.
 *  - **Remove**: pseudonymises the member row (clears personal fields,
 *    sets display_in_directory=0) and revokes all feed tokens.
 *
 * @since  __DEPLOY_VERSION__
 */
final class Cwmconnect extends PrivacyPlugin implements SubscriberInterface
{
    /**
     * @return  array<string, string>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPrivacyExportRequest' => 'onPrivacyExportRequest',
            'onPrivacyRemoveData'    => 'onPrivacyRemoveData',
        ];
    }

    /**
     * Export member data and feed tokens for a privacy request.
     *
     * @param   ExportRequestEvent  $event  The export request event.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onPrivacyExportRequest(ExportRequestEvent $event): void
    {
        $user = $event->getUser();

        if (!$user) {
            return;
        }

        $domains = [];
        $db      = $this->getDatabase();

        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $user->id, ParameterType::INTEGER);

        $items = $db->setQuery($query)->loadObjectList();

        if ($items !== []) {
            $domain = $this->createDomain('cwmconnect_member', 'plg_privacy_cwmconnect_member_data');

            foreach ($items as $item) {
                $domain->addItem($this->createItemFromArray((array) $item));
            }

            $domains[] = $domain;
            $domains[] = $this->createCustomFieldsDomain('com_cwmconnect.member', $items);
        }

        $tokenQuery = $db->createQuery()
            ->select([
                $db->quoteName('id'),
                $db->quoteName('label'),
                $db->quoteName('created_at'),
                $db->quoteName('last_used_at'),
                $db->quoteName('revoked_at'),
            ])
            ->from($db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $user->id, ParameterType::INTEGER);

        $tokens = $db->setQuery($tokenQuery)->loadObjectList();

        if ($tokens !== []) {
            $tokenDomain = $this->createDomain('cwmconnect_feed_tokens', 'plg_privacy_cwmconnect_feed_token_data');

            foreach ($tokens as $token) {
                $tokenDomain->addItem($this->createItemFromArray((array) $token));
            }

            $domains[] = $tokenDomain;
        }

        if ($domains !== []) {
            $event->addResult($domains);
        }
    }

    /**
     * Pseudonymise member data and revoke feed tokens for a removal request.
     *
     * Member rows are not deleted — they're pseudonymised (personal fields
     * cleared, display_in_directory set to 0) so the directory structure
     * stays intact. Feed tokens are revoked (not deleted) for audit trail.
     *
     * @param   RemoveDataEvent  $event  The removal request event.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onPrivacyRemoveData(RemoveDataEvent $event): void
    {
        $user = $event->getUser();

        if (!$user) {
            return;
        }

        $db  = $this->getDatabase();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $query = $db->createQuery()
            ->update($db->quoteName('#__cwmconnect_details'))
            ->set($db->quoteName('name') . ' = ' . $db->quote('Removed'))
            ->set($db->quoteName('lname') . ' = ' . $db->quote(''))
            ->set($db->quoteName('surname') . ' = ' . $db->quote(''))
            ->set($db->quoteName('email_to') . ' = NULL')
            ->set($db->quoteName('telephone') . ' = NULL')
            ->set($db->quoteName('mobile') . ' = NULL')
            ->set($db->quoteName('fax') . ' = NULL')
            ->set($db->quoteName('address') . ' = NULL')
            ->set($db->quoteName('suburb') . ' = NULL')
            ->set($db->quoteName('state') . ' = NULL')
            ->set($db->quoteName('postcode') . ' = NULL')
            ->set($db->quoteName('country') . ' = NULL')
            ->set($db->quoteName('webpage') . ' = NULL')
            ->set($db->quoteName('misc') . ' = NULL')
            ->set($db->quoteName('image') . ' = NULL')
            ->set($db->quoteName('birthdate') . ' = NULL')
            ->set($db->quoteName('anniversary') . ' = NULL')
            ->set($db->quoteName('display_in_directory') . ' = 0')
            ->set($db->quoteName('published') . ' = 0')
            ->set($db->quoteName('user_id') . ' = NULL')
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $user->id, ParameterType::INTEGER);

        $db->setQuery($query)->execute();

        $revokeQuery = $db->createQuery()
            ->update($db->quoteName('#__cwmconnect_feed_tokens'))
            ->set($db->quoteName('revoked_at') . ' = :now')
            ->where($db->quoteName('user_id') . ' = :userId')
            ->where($db->quoteName('revoked_at') . ' IS NULL')
            ->bind(':now', $now, ParameterType::STRING)
            ->bind(':userId', $user->id, ParameterType::INTEGER);

        $db->setQuery($revokeQuery)->execute();
    }
}
