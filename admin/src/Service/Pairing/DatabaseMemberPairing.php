<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\Pairing;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * DB-backed implementation of {@see MemberPairingInterface}. Production
 * binding registered in admin/services/provider.php.
 *
 * Tolerates both pre-H.1 (`user_id INT NOT NULL DEFAULT 0`) and post-H.1
 * (`user_id INT UNSIGNED NULL` + UNIQUE) schemas: the "unpaired" predicate
 * is `(user_id IS NULL OR user_id = 0)`, which is true under either shape.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class DatabaseMemberPairing implements MemberPairingInterface
{
    /**
     * @param   DatabaseInterface  $db         Database driver.
     * @param   MemberGroupSync|null  $groupSync  Grants the directory-member
     *                                            group on a successful pair.
     *                                            Null in contexts that only
     *                                            need the lookups (and in
     *                                            tests), where group
     *                                            membership is not in play.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private DatabaseInterface $db,
        private ?MemberGroupSync $groupSync = null,
    ) {}

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function findUnpairedMemberIdByEmail(string $email): ?int
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__cwmconnect_details'))
            ->where($this->db->quoteName('email_to') . ' = :email')
            ->where('(' . $this->db->quoteName('user_id') . ' IS NULL OR '
                . $this->db->quoteName('user_id') . ' = 0)')
            ->where($this->db->quoteName('published') . ' > -2')
            ->bind(':email', $email, ParameterType::STRING)
            ->setLimit(2);

        $ids = $this->db->setQuery($query)->loadColumn();

        return \count($ids) === 1 ? (int) $ids[0] : null;
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function findJoomlaUserIdByEmail(string $email): ?int
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        // Only an account whose current email is the one it last proved: anyone
        // can type a member's address into their own profile.
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('u.id'))
            ->from($this->db->quoteName('#__users', 'u'))
            ->join(
                'INNER',
                $this->db->quoteName('#__cwmconnect_verified_emails', 'v') . ' ON '
                    . $this->db->quoteName('v.user_id') . ' = ' . $this->db->quoteName('u.id')
                    . ' AND ' . $this->db->quoteName('v.email') . ' = ' . $this->db->quoteName('u.email'),
            )
            ->where($this->db->quoteName('u.email') . ' = :email')
            ->where($this->db->quoteName('u.block') . ' = 0')
            ->bind(':email', $email, ParameterType::STRING)
            ->setLimit(2);

        $ids = $this->db->setQuery($query)->loadColumn();

        return \count($ids) === 1 ? (int) $ids[0] : null;
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function recordVerifiedEmail(int $userId, string $email, string $source): void
    {
        $email = trim($email);

        if ($userId <= 0 || $email === '') {
            return;
        }

        // One row per account: a new proof replaces the old one.
        $this->forgetVerifiedEmail($userId);

        $row = (object) [
            'user_id'     => $userId,
            'email'       => $email,
            'source'      => $source,
            'verified_at' => new \DateTimeImmutable('now', new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        ];

        $this->db->insertObject('#__cwmconnect_verified_emails', $row);
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function hasVerifiedEmail(int $userId, string $email): bool
    {
        $email = trim($email);

        if ($userId <= 0 || $email === '') {
            return false;
        }

        $query = $this->db->createQuery()
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__cwmconnect_verified_emails'))
            ->where($this->db->quoteName('user_id') . ' = :userId')
            ->where($this->db->quoteName('email') . ' = :email')
            ->bind(':userId', $userId, ParameterType::INTEGER)
            ->bind(':email', $email, ParameterType::STRING);

        return (int) $this->db->setQuery($query)->loadResult() > 0;
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function forgetVerifiedEmail(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $query = $this->db->createQuery()
            ->delete($this->db->quoteName('#__cwmconnect_verified_emails'))
            ->where($this->db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();
    }

    /** @inheritDoc @since __DEPLOY_VERSION__ */
    public function pairMemberToUser(int $memberId, int $userId): bool
    {
        if ($memberId <= 0 || $userId <= 0) {
            return false;
        }

        $query = $this->db->createQuery()
            ->update($this->db->quoteName('#__cwmconnect_details'))
            ->set($this->db->quoteName('user_id') . ' = :userId')
            ->where($this->db->quoteName('id') . ' = :memberId')
            ->where('(' . $this->db->quoteName('user_id') . ' IS NULL OR '
                . $this->db->quoteName('user_id') . ' = 0)')
            ->bind(':userId', $userId, ParameterType::INTEGER)
            ->bind(':memberId', $memberId, ParameterType::INTEGER);

        $this->db->setQuery($query)->execute();

        if ($this->db->getAffectedRows() !== 1) {
            return false;
        }

        // Pairing is what makes someone a member of the directory rather than
        // merely a registered website account, so it is also what grants the
        // member group that carries the directory view level.
        $this->groupSync?->reconcile($userId);

        return true;
    }
}
