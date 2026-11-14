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

use CWM\Component\Cwmconnect\Administrator\Service\Access\MemberViewLevel;
use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

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
 * It also hears the component's Options being saved, which com_config does
 * after validating the form, and so after loading content plugins. When the
 * save changes the member view level, the members at the old level move to
 * the new one: see {@see MemberViewLevel}.
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
     * The member view level in force before the component's Options were
     * saved, between the before- and after-save events.
     *
     * @var    int|null
     * @since  __DEPLOY_VERSION__
     */
    private ?int $levelBefore = null;

    /**
     * @param   array<string, mixed>     $config     Plugin config row.
     * @param   MemberPairingInterface   $pairing    Identity-binding service.
     * @param   MemberViewLevel          $viewLevel  Moves members to a new member view level.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        array $config,
        private readonly MemberPairingInterface $pairing,
        private readonly MemberViewLevel $viewLevel,
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
            'onContentAfterSave'    => 'onContentAfterSave',
            'onExtensionBeforeSave' => 'onExtensionBeforeSave',
            'onExtensionAfterSave'  => 'onExtensionAfterSave',
        ];
    }

    /**
     * Handle a content save. Best-effort: a pair failure does not interrupt
     * the save (the event has already happened).
     *
     * @since  2.0.0
     */
    public function onContentAfterSave(EventInterface $event): void
    {
        $context = method_exists($event, 'getContext')
            ? $event->getContext()
            : ($event->getArgument('context', '') ?: '');

        $success = method_exists($event, 'getSavingResult')
            ? $event->getSavingResult()
            : (bool) ($event->getArgument('result', true));

        if ($context !== self::TARGET_CONTEXT || !$success) {
            return;
        }

        $item = method_exists($event, 'getItem')
            ? $event->getItem()
            : $event->getArgument('subject', null);

        if (!\is_object($item)) {
            return;
        }

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

    /**
     * Note the member view level in force before the component's Options are
     * saved.
     *
     * @param   EventInterface  $event  onExtensionBeforeSave.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onExtensionBeforeSave(EventInterface $event): void
    {
        if ($this->savedOptions($event) !== null) {
            $this->levelBefore = (int) ComponentHelper::getParams('com_cwmconnect')->get('member_access', 2);
        }
    }

    /**
     * Once the component's Options are saved with a new member view level,
     * move the members at the old one to it. Best-effort: the Options are
     * saved either way, and a failure is reported.
     *
     * @param   EventInterface  $event  onExtensionAfterSave.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function onExtensionAfterSave(EventInterface $event): void
    {
        $extension = $this->savedOptions($event);

        if ($extension === null || $this->levelBefore === null) {
            return;
        }

        $from              = $this->levelBefore;
        $this->levelBefore = null;
        $to                = (int) new Registry($extension->params ?? '')->get('member_access', 2);

        try {
            $moved = $this->viewLevel->change($from, $to);
        } catch (\Throwable $e) {
            $this->getApplication()->enqueueMessage(Text::sprintf('PLG_CONTENT_CWMCONNECT_MEMBERS_NOT_MOVED', $e->getMessage()), 'warning');

            return;
        }

        if ($moved > 0) {
            $this->getApplication()->enqueueMessage(Text::plural('PLG_CONTENT_CWMCONNECT_N_MEMBERS_MOVED', $moved));
        }
    }

    /**
     * The extension row when an event is com_config saving this component's
     * Options, or null for any other save.
     *
     * @param   EventInterface  $event  onExtensionBeforeSave or onExtensionAfterSave.
     *
     * @return  object|null
     *
     * @since   __DEPLOY_VERSION__
     */
    private function savedOptions(EventInterface $event): ?object
    {
        $context = method_exists($event, 'getContext')
            ? $event->getContext()
            : ($event->getArgument('context', '') ?: '');

        $extension = method_exists($event, 'getItem')
            ? $event->getItem()
            : $event->getArgument('subject', null);

        if ($context !== 'com_config.component' || !\is_object($extension) || ($extension->element ?? '') !== 'com_cwmconnect') {
            return null;
        }

        return $extension;
    }
}
