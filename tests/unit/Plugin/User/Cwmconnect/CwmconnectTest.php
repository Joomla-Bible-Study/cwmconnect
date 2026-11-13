<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Plugin\User\Cwmconnect;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use CWM\Plugin\User\Cwmconnect\Extension\Cwmconnect;
use Joomla\CMS\Event\User\AfterDeleteEvent;
use Joomla\CMS\Event\User\AfterSaveEvent;
use Joomla\CMS\Event\User\BeforeSaveEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase H pairing trigger #3: plg_user_cwmconnect's user-save handlers.
 *
 * Pairing decision matrix (onUserAfterSave):
 *  - save result false                     → nothing recorded, no pair attempt
 *  - user blocked (block != 0)             → no pair attempt
 *  - user has no email                     → no pair attempt
 *  - current email not the one it proved   → no pair attempt
 *  - proven email, no matching member      → looked up, not paired
 *  - proven email, member match found      → pairMemberToUser called once
 *
 * Proof (what gets recorded as the account's verified email):
 *  - administrator / API save that creates the account or changes its email,
 *    by someone else allowed to create / edit accounts, or any CLI save          → `admin`
 *  - the same clients saving without touching the email                         → nothing
 *  - an account changing its own email there, or a user without that right      → nothing
 *  - site save that spends a blocked account's token, address unchanged        → `activation`
 *    (the owner's activation click, whether or not administrators then approve)
 *  - the same, but the address changes too                                      → nothing
 *  - site save that leaves a blocked account's token alone                      → nothing
 *  - administrator approving a blocked account without that click first        → nothing
 *  - site save that completes a password reset (already active)                 → nothing
 *  - any other site save, including a self-service email change                 → nothing
 */
#[CoversClass(Cwmconnect::class)]
final class CwmconnectTest extends TestCase
{
    /** A signed-in administrator who may create and edit accounts. */
    private const ADMIN = ['id' => 1, 'can' => ['core.create', 'core.edit']];

    #[Test]
    public function failedSaveSkipsPairing(): void
    {
        $pairing = $this->stubPairing(verified: [7 => 'a@example.com'], memberIdByEmail: ['a@example.com' => 100]);
        $plugin  = $this->makePlugin($pairing, 'administrator');

        $plugin->onUserAfterSave($this->after(
            user: ['id' => 7, 'email' => 'a@example.com', 'block' => 0],
            savingResult: false,
        ));

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->memberLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function blockedUserSkipsPairing(): void
    {
        $pairing = $this->stubPairing(verified: [7 => 'a@example.com'], memberIdByEmail: ['a@example.com' => 100]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->after(
            user: ['id' => 7, 'email' => 'a@example.com', 'block' => 1],
            savingResult: true,
        ));

        self::assertSame(0, $pairing->memberLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function missingEmailSkipsPairing(): void
    {
        $pairing = $this->stubPairing();
        $plugin  = $this->makePlugin($pairing, 'administrator');

        $plugin->onUserAfterSave($this->after(
            user: ['id' => 7, 'email' => '', 'block' => 0],
            savingResult: true,
        ));

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->memberLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function noMatchingMemberLooksUpButDoesNotPair(): void
    {
        $pairing = $this->stubPairing(verified: [7 => 'nobody@example.com']);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->after(
            user: ['id' => 7, 'email' => 'nobody@example.com', 'block' => 0],
            savingResult: true,
        ));

        self::assertSame(1, $pairing->memberLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function matchingMemberTriggersExactlyOnePairCall(): void
    {
        $pairing = $this->stubPairing(verified: [7 => 'alice@example.com'], memberIdByEmail: ['alice@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->after(
            user: ['id' => 7, 'email' => 'alice@example.com', 'block' => 0],
            savingResult: true,
        ));

        self::assertSame(1, $pairing->memberLookups);
        self::assertSame(1, $pairing->pairCalls);
        self::assertSame(
            [['memberId' => 99, 'userId' => 7]],
            $pairing->pairCaptured,
        );
    }

    #[Test]
    public function aSelfServiceEmailChangeIsNeverPaired(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'own@example.com', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function aProvenAccountThatChangesItsEmailLosesTheTrust(): void
    {
        $pairing = $this->stubPairing(verified: [7 => 'own@example.com'], memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'own@example.com', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function anAdminSaveThatLeavesTheEmailAloneProvesNothing(): void
    {
        // The email was typed in by the account's owner; an admin then adds a
        // group or unblocks the account without touching it.
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'administrator');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'Member@Example.com ', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    #[DataProvider('adminClients')]
    public function anAdminChangingTheEmailProvesIt(string $client): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, $client, $client === 'cli' ? null : self::ADMIN);

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'old@example.com', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([['userId' => 7, 'email' => 'member@example.com', 'source' => 'admin']], $pairing->recorded);
        self::assertSame([['memberId' => 99, 'userId' => 7]], $pairing->pairCaptured);
    }

    #[Test]
    #[DataProvider('adminClients')]
    public function anAdminCreatingTheAccountProvesItsEmail(string $client): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, $client, $client === 'cli' ? null : self::ADMIN);

        // A new account has no stored row, so Joomla hands onUserBeforeSave id 0.
        $this->save(
            $plugin,
            before: ['id' => 0, 'email' => '', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
            isNew: true,
        );

        self::assertSame([['userId' => 7, 'email' => 'member@example.com', 'source' => 'admin']], $pairing->recorded);
        self::assertSame(1, $pairing->pairCalls);
    }

    #[Test]
    #[DataProvider('signedInClients')]
    public function anAccountChangingItsOwnEmailOutsideTheSiteProvesNothing(string $client): void
    {
        // Joomla lets anyone who can sign in to the backend edit their own
        // account there, whatever rights they have over other accounts.
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, $client, ['id' => 7, 'can' => ['core.create', 'core.edit']]);

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'own@example.com', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function changingAnotherAccountsEmailNeedsTheRightToEditAccounts(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'administrator', ['id' => 1, 'can' => ['core.create']]);

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'old@example.com', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function creatingAnAccountNeedsTheRightToCreateAccounts(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'administrator', ['id' => 1, 'can' => ['core.edit']]);

        $this->save(
            $plugin,
            before: ['id' => 0, 'email' => '', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
            isNew: true,
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function signedInClients(): iterable
    {
        yield 'administrator' => ['administrator'];
        yield 'api' => ['api'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adminClients(): iterable
    {
        yield 'administrator' => ['administrator'];
        yield 'api' => ['api'];
        yield 'cli' => ['cli'];
    }

    #[Test]
    public function followingTheActivationLinkProvesTheEmail(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'a-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([['userId' => 7, 'email' => 'member@example.com', 'source' => 'activation']], $pairing->recorded);
        self::assertSame([['memberId' => 99, 'userId' => 7]], $pairing->pairCaptured);
    }

    #[Test]
    public function inAdminApprovalModeTheOwnersClickProvesTheEmail(): void
    {
        // The owner's click swaps their token for one mailed to the
        // administrators, and the account stays blocked until one approves it.
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'owner-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'admin-token'],
        );

        self::assertSame([['userId' => 7, 'email' => 'member@example.com', 'source' => 'activation']], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function approvingFromTheUsersListAfterTheOwnersClickPairs(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);

        $this->save(
            $this->makePlugin($pairing, 'site'),
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'owner-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'admin-token'],
        );
        $this->save(
            $this->makePlugin($pairing, 'administrator'),
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'admin-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertCount(1, $pairing->recorded);
        self::assertSame([['memberId' => 99, 'userId' => 7]], $pairing->pairCaptured);
    }

    #[Test]
    public function approvingFromTheUsersListBeforeTheOwnerClicksProvesNothing(): void
    {
        // The token still waiting to be spent is the one mailed to the address
        // the registrant typed in, which may not be theirs.
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'administrator');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'owner-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function spendingTheTokenWhileChangingTheAddressProvesNothing(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'own@example.com', 'block' => 1, 'activation' => 'a-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function unblockingWithoutSpendingTheTokenProvesNothing(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 1, 'activation' => 'a-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => 'a-token'],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function completingAPasswordResetProvesNothing(): void
    {
        // The reset link went to the account's old address; by the time it is
        // followed the account has been switched to the member's.
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => 'a-reset-token'],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function registeringWithoutActivationProvesNothing(): void
    {
        // With account activation off, a new site registration is active at once.
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing, 'site');

        $this->save(
            $plugin,
            before: ['id' => 0, 'email' => '', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
            isNew: true,
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function withoutAnApplicationOnlyActivationCounts(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['member@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing);

        $this->save(
            $plugin,
            before: ['id' => 0, 'email' => '', 'block' => 0, 'activation' => ''],
            after: ['id' => 7, 'email' => 'member@example.com', 'block' => 0, 'activation' => ''],
            isNew: true,
        );

        self::assertSame([], $pairing->recorded);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function aDeletedAccountIsForgotten(): void
    {
        $pairing = $this->stubPairing(verified: [7 => 'a@example.com']);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterDelete(new AfterDeleteEvent(['id' => 7], false));
        $plugin->onUserAfterDelete(new AfterDeleteEvent(['id' => 7], true));

        self::assertSame([7], $pairing->forgotten);
    }

    /**
     * @param  array{id: int, can: list<string>}|null  $identity  Who is signed in, and what they may do in com_users.
     */
    private function makePlugin(MemberPairingInterface $pairing, ?string $client = null, ?array $identity = self::ADMIN): Cwmconnect
    {
        $plugin = new Cwmconnect([], $pairing);

        if ($client !== null) {
            $plugin->setApplication(new class ($client, $identity) {
                /** @param array{id: int, can: list<string>}|null $identity */
                public function __construct(private readonly string $client, private readonly ?array $identity) {}

                public function isClient(string $identifier): bool
                {
                    return $identifier === $this->client;
                }

                public function getIdentity(): ?object
                {
                    if ($this->identity === null) {
                        return null;
                    }

                    return new class ($this->identity['id'], $this->identity['can']) {
                        /** @param list<string> $can */
                        public function __construct(public readonly int $id, private readonly array $can) {}

                        public function authorise(string $action, ?string $asset = null): bool
                        {
                            return $asset === 'com_users' && \in_array($action, $this->can, true);
                        }
                    };
                }
            });
        }

        return $plugin;
    }

    /**
     * Run one save through both handlers, as Joomla's User::save() does.
     *
     * @param  array<string, mixed>  $before  The account as stored before the save.
     * @param  array<string, mixed>  $after   The account as saved.
     */
    private function save(Cwmconnect $plugin, array $before, array $after, bool $isNew = false): void
    {
        $plugin->onUserBeforeSave(new BeforeSaveEvent($before, $isNew, $after));
        $plugin->onUserAfterSave($this->after($after, true, $isNew));
    }

    /**
     * @param  array<string, mixed>  $user
     */
    private function after(array $user, bool $savingResult, bool $isNew = false): AfterSaveEvent
    {
        return new AfterSaveEvent($user, $isNew, $savingResult);
    }

    /**
     * @param  array<int, string>  $verified         user id => the email it has proven
     * @param  array<string, int>  $memberIdByEmail
     */
    private function stubPairing(array $verified = [], array $memberIdByEmail = [], bool $pairResult = true): MemberPairingInterface
    {
        return new class ($verified, $memberIdByEmail, $pairResult) implements MemberPairingInterface {
            public int $memberLookups = 0;

            public int $pairCalls = 0;

            /** @var list<array{memberId: int, userId: int}> */
            public array $pairCaptured = [];

            /** @var list<array{userId: int, email: string, source: string}> */
            public array $recorded = [];

            /** @var list<int> */
            public array $forgotten = [];

            /**
             * @param array<int, string> $verified
             * @param array<string, int> $memberIdByEmail
             */
            public function __construct(private array $verified, private array $memberIdByEmail, private bool $pairResult) {}

            public function findUnpairedMemberIdByEmail(string $email): ?int
            {
                $this->memberLookups++;

                return $this->memberIdByEmail[$email] ?? null;
            }

            public function findJoomlaUserIdByEmail(string $email): ?int
            {
                return null;  // not used by the user-side trigger
            }

            public function pairMemberToUser(int $memberId, int $userId): bool
            {
                $this->pairCalls++;
                $this->pairCaptured[] = ['memberId' => $memberId, 'userId' => $userId];

                return $this->pairResult;
            }

            public function recordVerifiedEmail(int $userId, string $email, string $source): void
            {
                $this->recorded[]         = ['userId' => $userId, 'email' => $email, 'source' => $source];
                $this->verified[$userId] = $email;
            }

            public function hasVerifiedEmail(int $userId, string $email): bool
            {
                // The table's collation makes the real comparison case-insensitive.
                return isset($this->verified[$userId]) && strcasecmp($this->verified[$userId], $email) === 0;
            }

            public function forgetVerifiedEmail(int $userId): void
            {
                $this->forgotten[] = $userId;
                unset($this->verified[$userId]);
            }
        };
    }
}
