<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\FeedToken;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Stateless service for KML feed token operations.
 *
 * Tokens are 32 random bytes hex-encoded (64 chars). The database stores
 * only the SHA-256 hash; the cleartext is returned to the caller exactly
 * once at creation time.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class FeedTokenService
{
    /**
     * Token is live and usable.
     *
     * @since  __DEPLOY_VERSION__
     */
    public const STATUS_ACTIVE = 'active';

    /**
     * Token was explicitly revoked.
     *
     * @since  __DEPLOY_VERSION__
     */
    public const STATUS_REVOKED = 'revoked';

    /**
     * Token lapsed — past its absolute expiry, or unused beyond the inactivity
     * window.
     *
     * @since  __DEPLOY_VERSION__
     */
    public const STATUS_EXPIRED = 'expired';

    /**
     * @param   DatabaseInterface  $db  Database connection.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private DatabaseInterface $db) {}

    /**
     * Pure lifecycle classification of a token row. Precedence:
     * revoked > expired > active.
     *
     * Inactivity is measured from `last_used_at` (or `created_at` when the
     * feed has never been refreshed). Because the live NetworkLink feed
     * auto-refreshes, an actively-used feed keeps sliding its deadline forward
     * and never lapses; only an abandoned feed does.
     *
     * @param   object                    $row             Token row (needs
     *                                                       revoked_at, expires_at,
     *                                                       last_used_at, created_at).
     * @param   int                       $inactivityDays  Sliding window in days;
     *                                                       0 disables inactivity expiry.
     * @param   \DateTimeImmutable|null    $now             Reference "now" (UTC); defaults
     *                                                       to the current time. Injectable
     *                                                       for testing.
     *
     * @return  string  One of the STATUS_* constants.
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function statusOf(object $row, int $inactivityDays = 0, ?\DateTimeImmutable $now = null): string
    {
        $utc = new \DateTimeZone('UTC');
        $now ??= new \DateTimeImmutable('now', $utc);

        if (!empty($row->revoked_at)) {
            return self::STATUS_REVOKED;
        }

        if (!empty($row->expires_at) && new \DateTimeImmutable((string) $row->expires_at, $utc) <= $now) {
            return self::STATUS_EXPIRED;
        }

        if ($inactivityDays > 0) {
            $reference = (string) ($row->last_used_at ?: ($row->created_at ?? ''));

            if ($reference !== ''
                && new \DateTimeImmutable($reference, $utc)->modify('+' . $inactivityDays . ' days') <= $now) {
                return self::STATUS_EXPIRED;
            }
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Generate a new token pair.
     *
     * @return  array{cleartext: string, hash: string}
     *
     * @since   __DEPLOY_VERSION__
     */
    public function generate(): array
    {
        $cleartext = bin2hex(random_bytes(32));

        return [
            'cleartext' => $cleartext,
            'hash'      => hash('sha256', $cleartext),
        ];
    }

    /**
     * Validate a cleartext token against the database.
     *
     * @param   string  $cleartext       The token from the URL query string.
     * @param   int     $inactivityDays  Sliding inactivity window in days; 0
     *                                    disables inactivity expiry. Callers pass
     *                                    the `kml_feed_inactivity_days` component
     *                                    param.
     *
     * @return  object|null  The token row, or null if invalid / revoked / expired.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function validate(string $cleartext, int $inactivityDays = 0): ?object
    {
        if ($cleartext === '') {
            return null;
        }

        $hash  = hash('sha256', $cleartext);
        $query = $this->db->createQuery()
            ->select('*')
            ->from($this->db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($this->db->quoteName('token_hash') . ' = :hash')
            ->where($this->db->quoteName('revoked_at') . ' IS NULL')
            ->bind(':hash', $hash, ParameterType::STRING);

        $row = $this->db->setQuery($query)->loadObject();

        if (!$row) {
            return null;
        }

        return self::statusOf($row, $inactivityDays) === self::STATUS_ACTIVE ? $row : null;
    }

    /**
     * Issue a brand-new feed token for a user. Always INSERTs a new row, so a
     * member's existing feeds are untouched and multiple named feeds coexist.
     *
     * `last_used_at` is seeded to now so a feed that is created but never loaded
     * into Google Earth still lapses after the inactivity window.
     *
     * @param   int          $userId     Joomla user id (the feed authenticates as
     *                                    this user).
     * @param   string       $label      Friendly name shown in the manage panel.
     * @param   string|null  $expiresAt  Optional absolute expiry as 'Y-m-d H:i:s'
     *                                    (UTC); null for no hard cutoff.
     *
     * @return  array{cleartext: string, id: int}  The one-time cleartext + new row id.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function issue(int $userId, string $label, ?string $expiresAt = null): array
    {
        $pair = $this->generate();
        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $row = (object) [
            'user_id'      => $userId,
            'token_hash'   => $pair['hash'],
            'label'        => $label,
            'created_at'   => $now,
            'last_used_at' => $now,
            'expires_at'   => $expiresAt,
        ];

        $this->db->insertObject('#__cwmconnect_feed_tokens', $row);

        return ['cleartext' => $pair['cleartext'], 'id' => (int) $this->db->insertid()];
    }

    /**
     * Rotate the secret of an existing token (e.g. the member lost their .kml).
     * The previous URL stops working; the new cleartext is returned once. The
     * inactivity clock is reset.
     *
     * Ownership is NOT checked here — the caller must verify the row belongs to
     * the acting user before calling.
     *
     * @param   int  $id  Token row id.
     *
     * @return  string  The new cleartext token.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function regenerate(int $id): string
    {
        $pair = $this->generate();
        $now  = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_feed_tokens'))
            ->set($this->db->quoteName('token_hash') . ' = :hash')
            ->set($this->db->quoteName('last_used_at') . ' = :now')
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':hash', $pair['hash'], ParameterType::STRING)
            ->bind(':now', $now, ParameterType::STRING)
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();

        return $pair['cleartext'];
    }

    /**
     * List a user's feed tokens, newest first, each tagged with a computed
     * `status` (see {@see statusOf()}). The `token_hash` is never returned.
     *
     * @param   int   $userId          Joomla user id.
     * @param   int   $inactivityDays  Inactivity window for the status calc.
     * @param   bool  $includeRevoked  Include revoked rows (default: hide them).
     *
     * @return  list<object>  Rows: id, user_id, label, created_at, last_used_at,
     *                        revoked_at, expires_at, status.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function listForUser(int $userId, int $inactivityDays = 0, bool $includeRevoked = false): array
    {
        $query = $this->db->createQuery()
            ->select(
                $this->db->quoteName(
                    ['id', 'user_id', 'label', 'created_at', 'last_used_at', 'revoked_at', 'expires_at'],
                ),
            )
            ->from($this->db->quoteName('#__cwmconnect_feed_tokens'))
            ->where($this->db->quoteName('user_id') . ' = :uid')
            ->order($this->db->quoteName('created_at') . ' DESC')
            ->bind(':uid', $userId, ParameterType::INTEGER);

        if (!$includeRevoked) {
            $query->where($this->db->quoteName('revoked_at') . ' IS NULL');
        }

        $rows = $this->db->setQuery($query)->loadObjectList() ?: [];

        foreach ($rows as $row) {
            $row->status = self::statusOf($row, $inactivityDays);
        }

        return $rows;
    }

    /**
     * Update the last_used_at timestamp for a token.
     *
     * @param   int  $id  Token row ID.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function touchLastUsed(int $id): void
    {
        $now   = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_feed_tokens'))
            ->set($this->db->quoteName('last_used_at') . ' = :now')
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':now', $now, ParameterType::STRING)
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }

    /**
     * Revoke one or more tokens by setting revoked_at.
     *
     * @param   list<int>  $ids  Token row IDs.
     *
     * @return  int  Number of rows affected.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function revoke(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $now   = new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_feed_tokens'))
            ->set($this->db->quoteName('revoked_at') . ' = :now')
            ->where($this->db->quoteName('revoked_at') . ' IS NULL')
            ->whereIn($this->db->quoteName('id'), $ids)
            ->bind(':now', $now, ParameterType::STRING);

        $this->db->setQuery($query)->execute();

        return $this->db->getAffectedRows();
    }
}
