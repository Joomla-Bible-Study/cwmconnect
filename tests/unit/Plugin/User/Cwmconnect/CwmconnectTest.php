<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Plugin\User\Cwmconnect;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use CWM\Plugin\User\Cwmconnect\Extension\Cwmconnect;
use Joomla\CMS\Event\User\AfterSaveEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase H pairing trigger #3: plg_user_cwmconnect's onUserAfterSave.
 *
 * Decision matrix:
 *  - save result false                  → no pair attempt
 *  - user blocked (block != 0)          → no pair attempt
 *  - user has no email                  → no pair attempt
 *  - email present, no matching member  → no pair attempt
 *  - email present, member match found  → pairMemberToUser called once
 */
#[CoversClass(Cwmconnect::class)]
final class CwmconnectTest extends TestCase
{
    #[Test]
    public function failedSaveSkipsPairing(): void
    {
        $pairing = $this->stubPairing();
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->event(
            user: ['id' => 7, 'email' => 'a@example.com', 'block' => 0],
            savingResult: false,
        ));

        self::assertSame(0, $pairing->memberLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function blockedUserSkipsPairing(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['a@example.com' => 100]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->event(
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
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->event(
            user: ['id' => 7, 'email' => '', 'block' => 0],
            savingResult: true,
        ));

        self::assertSame(0, $pairing->memberLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function noMatchingMemberLooksUpButDoesNotPair(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: []);  // no match
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->event(
            user: ['id' => 7, 'email' => 'nobody@example.com', 'block' => 0],
            savingResult: true,
        ));

        self::assertSame(1, $pairing->memberLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function matchingMemberTriggersExactlyOnePairCall(): void
    {
        $pairing = $this->stubPairing(memberIdByEmail: ['alice@example.com' => 99]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onUserAfterSave($this->event(
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

    private function makePlugin(MemberPairingInterface $pairing): Cwmconnect
    {
        return new Cwmconnect([], $pairing);
    }

    /**
     * @param  array<string, mixed>  $user
     */
    private function event(array $user, bool $savingResult, bool $isNew = true): AfterSaveEvent
    {
        return new AfterSaveEvent($user, $isNew, $savingResult);
    }

    /**
     * @param  array<string, int>  $memberIdByEmail
     */
    private function stubPairing(array $memberIdByEmail = [], bool $pairResult = true): MemberPairingInterface
    {
        return new class ($memberIdByEmail, $pairResult) implements MemberPairingInterface {
            public int $memberLookups = 0;

            public int $pairCalls = 0;

            /** @var list<array{memberId: int, userId: int}> */
            public array $pairCaptured = [];

            /** @param array<string, int> $memberIdByEmail */
            public function __construct(private array $memberIdByEmail, private bool $pairResult) {}

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
        };
    }
}
