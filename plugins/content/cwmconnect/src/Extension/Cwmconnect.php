<?php

/**
 * @package    Plg_Content_Cwmconnect
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

declare(strict_types=1);

namespace CWM\Plugin\Content\Cwmconnect\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use Joomla\CMS\Event\Content\AfterSaveEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * Content plugin — Phase H trigger #4: when a member row is saved through
 * the standard com_cwmconnect admin AdminModel flow, try to pair the row
 * to a Joomla user that shares the same email address (spec §8.2).
 *
 * Context scope: `com_cwmconnect.member` only. All other content saves are
 * ignored so we never touch articles, contacts, or other components.
 *
 * Pair attempts are guarded:
 *  - Already-paired rows are never overwritten (spec §8.2: admin must unlink
 *    first); the underlying `pairMemberToUser()` enforces this with a
 *    `user_id IS NULL` predicate.
 *  - Ambiguous email matches return null from `findJoomlaUserIdByEmail()`
 *    and are silently skipped (matches the spec heuristic).
 *
 * @since  2.0.0
 */
final class Cwmconnect extends CMSPlugin implements SubscriberInterface
{
    /**
     * Content context the plugin acts on. Anything else is a no-op.
     *
     * @since  2.0.0
     */
    public const TARGET_CONTEXT = 'com_cwmconnect.member';

    /** @var bool */
    protected $autoloadLanguage = true;

    /**
     * @param   array<string, mixed>          $config   Plugin config row.
     * @param   MemberPairingInterface        $pairing  Identity-binding service.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        array $config,
        private readonly MemberPairingInterface $pairing,
    ) {
        parent::__construct($config);
    }

    /**
     * @return  array<string, string>
     *
     * @since  2.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentAfterSave' => 'onContentAfterSave',
        ];
    }

    /**
     * Handle a content save. Best-effort: a pair failure does not interrupt
     * the save (the event has already happened).
     *
     * @since  2.0.0
     */
    public function onContentAfterSave(AfterSaveEvent $event): void
    {
        if ($event->getContext() !== self::TARGET_CONTEXT || !$event->getSavingResult()) {
            return;
        }

        $item     = $event->getItem();
        $memberId = (int) ($item->id ?? 0);
        $email    = isset($item->email_to) && \is_string($item->email_to) ? trim($item->email_to) : '';
        $current  = (int) ($item->user_id ?? 0);

        if ($memberId <= 0 || $email === '' || $current > 0) {
            return;
        }

        $userId = $this->pairing->findJoomlaUserIdByEmail($email);

        if ($userId === null) {
            return;
        }

        $this->pairing->pairMemberToUser($memberId, $userId);
    }
}
