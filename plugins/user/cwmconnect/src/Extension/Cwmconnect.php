<?php

/**
 * @package    Plg_User_Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Plugin\User\Cwmconnect\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use Joomla\CMS\Event\User\AfterSaveEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * User plugin — Phase H trigger #3: when a Joomla user is saved, try to
 * pair the account to an unpaired member row that shares the same email
 * address (spec §8.2).
 *
 * Fires on every save (create, edit, activation) but only acts when:
 *  - the save succeeded,
 *  - the user is active (block = 0), so we don't pair to pending accounts,
 *  - the user has a non-empty email, and
 *  - exactly one unpaired member shares that email.
 *
 * The pair call itself is guarded — already-paired members are never
 * overwritten. Ambiguous matches (two members with the same email) are
 * silently skipped; the admin must resolve them via the manual pair UI.
 *
 * @since  2.0.0
 */
final class Cwmconnect extends CMSPlugin implements SubscriberInterface
{
    /** @var bool */
    protected $autoloadLanguage = true;

    public function __construct(
        array $config,
        private readonly MemberPairingInterface $pairing,
    ) {
        parent::__construct(null, $config);
    }

    /**
     * @return  array<string, string>
     *
     * @since  2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onUserAfterSave' => 'onUserAfterSave',
        ];
    }

    /**
     * Handle a successful user save. Best-effort: a pair failure does not
     * interrupt the user save (the event has already happened).
     *
     * @since  2.0.0
     */
    public function onUserAfterSave(AfterSaveEvent $event): void
    {
        if (!$event->getSavingResult()) {
            return;
        }

        $user   = $event->getUser();
        $userId = (int) ($user['id'] ?? 0);
        $email  = isset($user['email']) && \is_string($user['email']) ? trim($user['email']) : '';
        $block  = (int) ($user['block'] ?? 1);

        if ($userId <= 0 || $email === '' || $block !== 0) {
            return;
        }

        $memberId = $this->pairing->findUnpairedMemberIdByEmail($email);

        if ($memberId === null) {
            return;
        }

        $this->pairing->pairMemberToUser($memberId, $userId);
    }
}
