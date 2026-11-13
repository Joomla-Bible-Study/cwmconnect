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

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\DatabaseMemberPairing;
use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberGroupSync;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\CachedPhotos;
use CWM\Component\Cwmconnect\Administrator\Service\Privacy\DatabasePrivacySuppressions;
use CWM\Component\Cwmconnect\Administrator\Service\Privacy\MemberErasure;
use CWM\Component\Cwmconnect\Administrator\Service\Search\FinderSearchIndex;
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
 *    (via user_id FK), any feed tokens they own, and the email address the
 *    account proved for pairing.
 *  - **Remove**: erases the member (see {@see MemberErasure}): the row is
 *    blanked and archived, the Planning Center sync stops refilling it, and
 *    the person's custom-field values and cached photos go. It also revokes
 *    all feed tokens and forgets the proven email address.
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
     * Export member data, feed tokens and the proven email for a privacy
     * request.
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

        $verifiedQuery = $db->createQuery()
            ->select([
                $db->quoteName('email'),
                $db->quoteName('source'),
                $db->quoteName('verified_at'),
            ])
            ->from($db->quoteName('#__cwmconnect_verified_emails'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $user->id, ParameterType::INTEGER);

        $verified = $db->setQuery($verifiedQuery)->loadObjectList();

        if ($verified !== []) {
            $verifiedDomain = $this->createDomain('cwmconnect_verified_email', 'plg_privacy_cwmconnect_verified_email_data');

            foreach ($verified as $row) {
                $verifiedDomain->addItem($this->createItemFromArray((array) $row));
            }

            $domains[] = $verifiedDomain;
        }

        if ($domains !== []) {
            $event->addResult($domains);
        }
    }

    /**
     * Erase the member behind a removal request, revoke their feed tokens and
     * forget their proven email.
     *
     * The member row stays, blanked and archived, so that an administrator can
     * restore a Planning Center member by taking it out of the archive; until
     * then the sync leaves it blank. Feed tokens are revoked (not deleted) for
     * the audit trail.
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

        $db     = $this->getDatabase();
        $now    = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $userId = (int) $user->id;

        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__cwmconnect_details'))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $erasure = new MemberErasure(
            $db,
            new DatabasePrivacySuppressions($db),
            new CachedPhotos(JPATH_ROOT . '/media/com_cwmconnect/photos'),
            new FinderSearchIndex($db),
        );

        $memberIds = array_map('intval', $db->setQuery($query)->loadColumn());

        foreach ($memberIds as $memberId) {
            $erasure->erase($memberId);
        }

        $revokeQuery = $db->createQuery()
            ->update($db->quoteName('#__cwmconnect_feed_tokens'))
            ->set($db->quoteName('revoked_at') . ' = :now')
            ->where($db->quoteName('user_id') . ' = :userId')
            ->where($db->quoteName('revoked_at') . ' IS NULL')
            ->bind(':now', $now, ParameterType::STRING)
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $db->setQuery($revokeQuery)->execute();

        $groupSync = MemberGroupSync::configured($db);

        new DatabaseMemberPairing($db, $groupSync)->forgetVerifiedEmail($userId);

        // The rows above were just unpaired and archived, so this user is no
        // longer a member: drop the group that carries directory access.
        // Reconciled from the database, so it is correct even if they somehow
        // still hold another member row.
        $groupSync->reconcile($userId);
    }
}
