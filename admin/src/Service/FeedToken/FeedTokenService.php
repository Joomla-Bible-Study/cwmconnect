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
     * @param   DatabaseInterface  $db  Database connection.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private DatabaseInterface $db) {}

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
     * @param   string  $cleartext  The token from the URL query string.
     *
     * @return  object|null  The token row, or null if invalid/revoked.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function validate(string $cleartext): ?object
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

        return $row ?: null;
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
