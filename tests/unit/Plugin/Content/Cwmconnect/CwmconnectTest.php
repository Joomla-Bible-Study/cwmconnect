<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Plugin\Content\Cwmconnect;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use CWM\Plugin\Content\Cwmconnect\Extension\Cwmconnect;
use Joomla\CMS\Event\Content\AfterSaveEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase H pairing trigger #4: plg_content_cwmconnect's onContentAfterSave.
 *
 * Decision matrix:
 *  - wrong context (e.g. com_content.article)  → no pair attempt
 *  - savingResult false                        → no pair attempt
 *  - row has no email                          → no pair attempt
 *  - row already paired (user_id > 0)          → no pair attempt
 *  - email present, no matching user           → no pair attempt
 *  - email present, user match found           → pairMemberToUser called once
 */
#[CoversClass(Cwmconnect::class)]
final class CwmconnectTest extends TestCase
{
    #[Test]
    public function wrongContextSkipsPairing(): void
    {
        $pairing = $this->stubPairing(userIdByEmail: ['a@example.com' => 7]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onContentAfterSave(new AfterSaveEvent(
            'com_content.article',
            (object) ['id' => 99, 'email_to' => 'a@example.com', 'user_id' => 0],
        ));

        self::assertSame(0, $pairing->userLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function failedSaveSkipsPairing(): void
    {
        $pairing = $this->stubPairing(userIdByEmail: ['a@example.com' => 7]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onContentAfterSave(new AfterSaveEvent(
            'com_cwmconnect.member',
            (object) ['id' => 99, 'email_to' => 'a@example.com', 'user_id' => 0],
            savingResult: false,
        ));

        self::assertSame(0, $pairing->userLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function missingEmailSkipsPairing(): void
    {
        $pairing = $this->stubPairing();
        $plugin  = $this->makePlugin($pairing);

        $plugin->onContentAfterSave(new AfterSaveEvent(
            'com_cwmconnect.member',
            (object) ['id' => 99, 'email_to' => '', 'user_id' => 0],
        ));

        self::assertSame(0, $pairing->userLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function alreadyPairedRowSkipsPairing(): void
    {
        $pairing = $this->stubPairing(userIdByEmail: ['a@example.com' => 7]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onContentAfterSave(new AfterSaveEvent(
            'com_cwmconnect.member',
            (object) ['id' => 99, 'email_to' => 'a@example.com', 'user_id' => 42],
        ));

        self::assertSame(0, $pairing->userLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function noMatchingUserLooksUpButDoesNotPair(): void
    {
        $pairing = $this->stubPairing(userIdByEmail: []);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onContentAfterSave(new AfterSaveEvent(
            'com_cwmconnect.member',
            (object) ['id' => 99, 'email_to' => 'nobody@example.com', 'user_id' => 0],
        ));

        self::assertSame(1, $pairing->userLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function matchingUserTriggersExactlyOnePairCall(): void
    {
        $pairing = $this->stubPairing(userIdByEmail: ['alice@example.com' => 7]);
        $plugin  = $this->makePlugin($pairing);

        $plugin->onContentAfterSave(new AfterSaveEvent(
            'com_cwmconnect.member',
            (object) ['id' => 99, 'email_to' => 'alice@example.com', 'user_id' => 0],
        ));

        self::assertSame(1, $pairing->userLookups);
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
     * @param  array<string, int>  $userIdByEmail
     */
    private function stubPairing(array $userIdByEmail = [], bool $pairResult = true): MemberPairingInterface
    {
        return new class ($userIdByEmail, $pairResult) implements MemberPairingInterface {
            public int $userLookups = 0;

            public int $pairCalls = 0;

            /** @var list<array{memberId: int, userId: int}> */
            public array $pairCaptured = [];

            /** @param array<string, int> $userIdByEmail */
            public function __construct(private array $userIdByEmail, private bool $pairResult) {}

            public function findUnpairedMemberIdByEmail(string $email): ?int
            {
                return null;
            }

            public function findJoomlaUserIdByEmail(string $email): ?int
            {
                $this->userLookups++;

                return $this->userIdByEmail[$email] ?? null;
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
