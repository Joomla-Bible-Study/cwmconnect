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

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;

/**
 * User plugin — Phase H trigger #3: when a Joomla user is saved, try to
 * pair the account to an unpaired member row that shares the same email
 * address (spec §8.2).
 *
 * Pairing hands over that member's profile, so it only trusts an email the
 * account has proven is its own, and this plugin is what records the proof.
 * A save proves the account's email in exactly two cases:
 *  - someone with authority over other accounts created the account, or
 *    changed its email in this save: a signed-in user allowed to create or
 *    edit accounts, saving one that is not their own, in the administrator
 *    or through the API, or the command line, which needs the server. Joomla
 *    lets anyone who can sign in to the backend edit their own account
 *    there, and saving an account for any other reason proves nothing about
 *    an email its owner may have set; and
 *  - the account's owner followed the activation link mailed to its address
 *    when it registered: the account was blocked with an activation token,
 *    and this save spent the token without changing the address. A blocked
 *    account cannot sign in to change its email, so the address is still the
 *    one the link went to. When administrators approve new accounts, that
 *    click swaps the owner's token for one mailed to the administrators;
 *    their token only exists once the owner has clicked, so spending it
 *    proves the same address again. Either way the account pairs once it is
 *    approved, from the administrators' link or from the Users list.
 *    A password reset spends the same kind of token, but on an account that
 *    is already active, which can change its address at will, so it does
 *    not count.
 *
 * Then it pairs when:
 *  - the save succeeded,
 *  - the user is active (block = 0), so we don't pair to pending accounts,
 *  - the user's current email is the one it last proved, and
 *  - exactly one unpaired member shares that email.
 *
 * The pair call itself is guarded — already-paired members are never
 * overwritten. Ambiguous matches (two members with the same email) are
 * silently skipped; the admin must resolve them via the manual pair UI.
 *
 * When an account is deleted, it lets go of everything the directory held
 * for it: its proven email, its member, who can then pair with another
 * account, and its map feeds.
 *
 * @since  2.0.0
 */
final class Cwmconnect extends CMSPlugin implements SubscriberInterface
{
    /** @var bool */
    protected $autoloadLanguage = true;

    /**
     * Each account's `block`, `activation` and `email` from just before the
     * save in progress, keyed by user id, for onUserAfterSave to compare.
     *
     * @var    array<int, array{block: int, activation: string, email: string}>
     * @since  __DEPLOY_VERSION__
     */
    private array $before = [];

    /**
     * @param   array<string, mixed>     $config      Plugin config row.
     * @param   MemberPairingInterface   $pairing     Identity-binding service.
     * @param   FeedTokenService|null    $feedTokens  Map feed tokens, which a deleted account's
     *                                                are revoked from. Null leaves them.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        array $config,
        private readonly MemberPairingInterface $pairing,
        private readonly ?FeedTokenService $feedTokens = null,
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
            'onUserBeforeSave'  => 'onUserBeforeSave',
            'onUserAfterSave'   => 'onUserAfterSave',
            'onUserAfterDelete' => 'onUserAfterDelete',
        ];
    }

    /**
     * Remember the account as it was, so the after-save handler can tell
     * whether this save proved its email.
     *
     * @param   EventInterface  $event  The before-save event.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onUserBeforeSave(EventInterface $event): void
    {
        $old = (array) (method_exists($event, 'getUser')
            ? $event->getUser()
            : $event->getArgument('subject', []));
        $id = (int) ($old['id'] ?? 0);

        if ($id <= 0) {
            return;
        }

        $this->before[$id] = [
            'block'      => (int) ($old['block'] ?? 0),
            'activation' => (string) ($old['activation'] ?? ''),
            'email'      => trim((string) ($old['email'] ?? '')),
        ];
    }

    /**
     * Handle a successful user save. Best-effort: a pair failure does not
     * interrupt the user save (the event has already happened).
     *
     * @param   EventInterface  $event  The after-save event.
     *
     * @return  void
     *
     * @since  2.0.0
     */
    public function onUserAfterSave(EventInterface $event): void
    {
        $success = method_exists($event, 'getSavingResult')
            ? $event->getSavingResult()
            : (bool) ($event->getArgument('result', true));

        $user = method_exists($event, 'getUser')
            ? $event->getUser()
            : $event->getArgument('subject', []);

        if (!\is_array($user)) {
            $user = (array) $user;
        }

        $userId = (int) ($user['id'] ?? 0);
        $before = $this->before[$userId] ?? null;
        unset($this->before[$userId]);

        if (!$success) {
            return;
        }

        $email = isset($user['email']) && \is_string($user['email']) ? trim($user['email']) : '';
        $block = (int) ($user['block'] ?? 1);
        $isNew = method_exists($event, 'getIsNew')
            ? (bool) $event->getIsNew()
            : (bool) $event->getArgument('isNew', false);

        if ($userId <= 0 || $email === '') {
            return;
        }

        $proof = $this->proofOfEmail($isNew, $before, $user, $email);

        if ($proof !== null) {
            $this->pairing->recordVerifiedEmail($userId, $email, $proof);
        }

        if ($block !== 0 || !$this->pairing->hasVerifiedEmail($userId, $email)) {
            return;
        }

        $memberId = $this->pairing->findUnpairedMemberIdByEmail($email);

        if ($memberId === null) {
            return;
        }

        $this->pairing->pairMemberToUser($memberId, $userId);
    }

    /**
     * Let go of what the directory held for a deleted account: its proven
     * email, the pairing of its member, who can then pair with another
     * account, and its map feeds, which had stopped working with it.
     *
     * @param   EventInterface  $event  The after-delete event.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onUserAfterDelete(EventInterface $event): void
    {
        $success = method_exists($event, 'getDeletingResult')
            ? $event->getDeletingResult()
            : (bool) ($event->getArgument('deletingResult', true));

        if (!$success) {
            return;
        }

        $user = (array) (method_exists($event, 'getUser')
            ? $event->getUser()
            : $event->getArgument('subject', []));

        $userId = (int) ($user['id'] ?? 0);

        $this->pairing->forgetVerifiedEmail($userId);
        $this->pairing->unpairUser($userId);
        $this->feedTokens?->revokeAllForUser($userId);
    }

    /**
     * How this save proved the account's email, or null if it did not. The
     * two cases, and why a password reset is not one, are in the class
     * comment.
     *
     * @param   bool                                                      $isNew   Whether the save created the account.
     * @param   array{block: int, activation: string, email: string}|null  $before  The account just before the save.
     * @param   array<string, mixed>                                      $user    The account as saved.
     * @param   string                                                    $email   Its email, trimmed.
     *
     * @return  string|null  `admin`, `activation`, or null.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function proofOfEmail(bool $isNew, ?array $before, array $user, string $email): ?string
    {
        $app = $this->getApplication();

        if ($app !== null && !$app->isClient('site')) {
            $changed  = $before !== null && strcasecmp($before['email'], $email) !== 0;
            $identity = $app->getIdentity();
            $vouched  = $app->isClient('cli')
                || ($identity !== null
                    && (int) $identity->id !== (int) ($user['id'] ?? 0)
                    && $identity->authorise($isNew ? 'core.create' : 'core.edit', 'com_users'));

            return ($isNew || $changed) && $vouched ? 'admin' : null;
        }

        $spent = $before !== null
            && $before['block'] === 1
            && $before['activation'] !== ''
            && (string) ($user['activation'] ?? '') !== $before['activation']
            && strcasecmp($before['email'], $email) === 0;

        return $spent ? 'activation' : null;
    }
}
