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
    /** @since __DEPLOY_VERSION__ */
    public function __construct(private DatabaseInterface $db) {}

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

        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__users'))
            ->where($this->db->quoteName('email') . ' = :email')
            ->where($this->db->quoteName('block') . ' = 0')
            ->bind(':email', $email, ParameterType::STRING)
            ->setLimit(2);

        $ids = $this->db->setQuery($query)->loadColumn();

        return \count($ids) === 1 ? (int) $ids[0] : null;
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

        return $this->db->getAffectedRows() === 1;
    }
}
