<?php

/**
 * @package    Cwmconnect.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Administrator\Service\AccessCode;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;

/**
 * The shared access code: one code, announced to the congregation, that a
 * registered user redeems once to be put into the group carrying directory
 * access.
 *
 * Solves the enrolment problem that pairing cannot. Pairing matches on the
 * email Planning Center holds, and most members have none — 316 of 540 on the
 * reference dataset — so no amount of syncing will ever let them in. A code
 * read out from the front, or mailed round, reaches all of them at once and
 * costs an admin nothing per person. That is the whole point of it being
 * *shared*: a per-person code would be no less work than adding each user to
 * the group by hand.
 *
 * The trade is that a shared secret leaks, so it is deliberately weak tea:
 * it grants one view level and nothing else, it can carry an expiry, and it
 * is rotated from Options in a click. It is never a login, never appears in a
 * URL, and is only ever accepted from an already-authenticated session.
 *
 * **Redeeming does not pair anyone.** The user gains the directory view but no
 * member record, so they get no My Profile, no self-service KML feed and no
 * privacy-export coverage. That is why the granted group is kept separate from
 * the pairing-managed one — see {@see \CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberGroupSync}
 * which reconciles *its* group from pairing state and would otherwise strip a
 * code-granted user right back out again.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class AccessCodeService
{
    /**
     * Alphabet for generated codes: digits and upper-case letters with the
     * shapes people mistranscribe removed (0/O, 1/I/L, 5/S, 8/B, U/V), because
     * these get read aloud and copied off paper.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const ALPHABET = '234679ACDEFGHJKMNPQRTWXYZ';

    /**
     * Generated code length, before grouping.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const LENGTH = 8;

    /**
     * @param   Registry  $params  Component parameters.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(private Registry $params) {}

    /**
     * Mint a fresh code, grouped for legibility (e.g. `K7F2-9XQM`).
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function generate(): string
    {
        $max  = \strlen(self::ALPHABET) - 1;
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return substr($code, 0, 4) . '-' . substr($code, 4);
    }

    /**
     * Strip formatting so `k7f2 9xqm`, `K7F2-9XQM` and `k7f29xqm` all compare
     * equal — the code is transcribed by hand, so punctuation and case are
     * noise rather than signal.
     *
     * @param   string  $code  Raw input.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function canonical(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /**
     * Is the feature switched on, with a code and a target group configured?
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function isEnabled(): bool
    {
        return (int) $this->params->get('access_code_enabled', 0) === 1
            && self::canonical((string) $this->params->get('access_code', '')) !== ''
            && $this->groupId() > 0;
    }

    /**
     * The group a successful redemption grants.
     *
     * @return  int
     *
     * @since   __DEPLOY_VERSION__
     */
    public function groupId(): int
    {
        return (int) $this->params->get('access_code_group', 0);
    }

    /**
     * Has the configured code passed its expiry date?
     *
     * @return  bool  False when no expiry is set.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function hasExpired(): bool
    {
        $expires = trim((string) $this->params->get('access_code_expires', ''));

        if ($expires === '' || str_starts_with($expires, '0000-00-00')) {
            return false;
        }

        try {
            $deadline = new \DateTimeImmutable($expires, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            // An unparseable date must not silently mean "never expires".
            return true;
        }

        return new \DateTimeImmutable('now', new \DateTimeZone('UTC')) > $deadline;
    }

    /**
     * Check a submitted code against the configured one.
     *
     * Compared with {@see hash_equals()} on the canonical forms so the check
     * does not leak the code through response timing.
     *
     * @param   string  $submitted  What the user typed.
     *
     * @return  bool
     *
     * @since   __DEPLOY_VERSION__
     */
    public function matches(string $submitted): bool
    {
        if (!$this->isEnabled() || $this->hasExpired()) {
            return false;
        }

        $expected = self::canonical((string) $this->params->get('access_code', ''));
        $actual   = self::canonical($submitted);

        return $actual !== '' && hash_equals($expected, $actual);
    }

    /**
     * Put a user into the granted group.
     *
     * Separate from {@see self::matches()} so the caller can rate-limit, log
     * and message around the decision without this class knowing about any of
     * that.
     *
     * @param   int  $userId  Joomla user id.
     *
     * @return  bool  False when the feature is off or the id is not a user.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function grant(int $userId): bool
    {
        if ($userId <= 0 || !$this->isEnabled()) {
            return false;
        }

        return UserHelper::addUserToGroup($userId, $this->groupId());
    }
}
