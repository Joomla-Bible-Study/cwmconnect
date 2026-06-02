<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MemberRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\UpsertOutcome;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase H: SyncEngine email-match pairing step (spec §8.2 trigger #1).
 *
 * Decision matrix:
 *  - no pairing service wired                           → never call repo
 *  - PC person has no email                             → never call pairing
 *  - email present, no Joomla user matches              → no pair, no error
 *  - email present, user matches, member already paired → no counter bump
 *  - email present, user matches, pair succeeds         → report.paired++
 *  - pairing throws                                     → captured in errors
 */
#[CoversClass(SyncEngine::class)]
final class SyncEnginePhaseHTest extends TestCase
{
    #[Test]
    public function pairingIsSkippedEntirelyWhenNoServiceIsWired(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithEmail(1, 'a@example.com')));
        $repo    = $this->stubRepo();

        $report = new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(0, $report->paired);
        self::assertTrue($report->success());
    }

    #[Test]
    public function personWithoutEmailDoesNotConsultPairingService(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithEmail(1, null)));
        $repo    = $this->stubRepo();
        $pairing = $this->stubPairing();

        $report = new SyncEngine(
            $client,
            $repo,
            new PersonMapper(),
            null,
            null,
            null,
            $pairing,
        )->run([]);

        self::assertSame(0, $report->paired);
        self::assertSame(0, $pairing->userLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function emailWithNoMatchingUserSkipsPair(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithEmail(1, 'nobody@example.com')));
        $repo    = $this->stubRepo();
        $pairing = $this->stubPairing(userIdByEmail: []);  // no users match

        $report = new SyncEngine(
            $client,
            $repo,
            new PersonMapper(),
            null,
            null,
            null,
            $pairing,
        )->run([]);

        self::assertSame(0, $report->paired);
        self::assertSame(1, $pairing->userLookups);
        self::assertSame(0, $pairing->pairCalls);
    }

    #[Test]
    public function matchingEmailIncrementsPairedAndCallsPairExactlyOnce(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithEmail(1, 'alice@example.com')));
        $repo    = $this->stubRepo();
        $pairing = $this->stubPairing(
            userIdByEmail: ['alice@example.com' => 42],
            pairResult: true,
        );

        $report = new SyncEngine(
            $client,
            $repo,
            new PersonMapper(),
            null,
            null,
            null,
            $pairing,
        )->run([]);

        self::assertSame(1, $report->paired);
        self::assertSame(1, $pairing->pairCalls);
        self::assertSame(
            [['memberId' => 1001, 'userId' => 42]],
            $pairing->pairCaptured,
        );
    }

    #[Test]
    public function alreadyPairedMemberDoesNotIncrementCounter(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithEmail(1, 'alice@example.com')));
        $repo    = $this->stubRepo();
        $pairing = $this->stubPairing(
            userIdByEmail: ['alice@example.com' => 42],
            pairResult: false,  // UPDATE affected 0 rows = already paired
        );

        $report = new SyncEngine(
            $client,
            $repo,
            new PersonMapper(),
            null,
            null,
            null,
            $pairing,
        )->run([]);

        self::assertSame(0, $report->paired);
        self::assertSame(1, $pairing->pairCalls);
        self::assertTrue($report->success());
    }

    #[Test]
    public function pairingExceptionIsCapturedAndDoesNotAbortRun(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithEmail(1, 'boom@example.com')));
        $repo    = $this->stubRepo();
        $pairing = $this->stubPairingThatThrows();

        $report = new SyncEngine(
            $client,
            $repo,
            new PersonMapper(),
            null,
            null,
            null,
            $pairing,
        )->run([]);

        self::assertSame(0, $report->paired);
        self::assertSame(1, $report->errorCount());
        self::assertStringContainsString('Pair-by-email', $report->errors[0]['message']);
    }

    /**
     * @param  array<string, mixed>  $page
     */
    private function clientReturning(array $page): Client
    {
        return new class ($page) extends Client {
            public function __construct(private array $page)
            {
                // bypass parent
            }

            public function getJson(string $path, array $query = []): array
            {
                return $this->page;
            }

            public function getJsonAbsolute(string $url): array
            {
                return ['data' => [], 'included' => [], 'links' => []];
            }
        };
    }

    private function stubRepo(): MemberRepositoryInterface
    {
        return new class implements MemberRepositoryInterface {
            public function upsertByPcPersonId(array $attrs): UpsertOutcome
            {
                return UpsertOutcome::Added;
            }

            public function deleteMissingPcPersonIds(array $seenPcPersonIds): int
            {
                return 0;
            }

            public function findIdByPcPersonId(int $pcPersonId): ?int
            {
                return 1000 + $pcPersonId;
            }

            public function updateImageByPcPersonId(int $pcPersonId, string $relativePath, string $hash): void
            {
                // not exercised in pair tests
            }

            public function findImageHashByPcPersonId(int $pcPersonId): ?string
            {
                return null;
            }
        };
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
                // Engine does not invoke this on the PC-sync trigger — it
                // already knows the member id via findIdByPcPersonId.
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

    private function stubPairingThatThrows(): MemberPairingInterface
    {
        return new class implements MemberPairingInterface {
            public function findUnpairedMemberIdByEmail(string $email): ?int
            {
                return null;
            }

            public function findJoomlaUserIdByEmail(string $email): ?int
            {
                throw new \RuntimeException('DB down');
            }

            public function pairMemberToUser(int $memberId, int $userId): bool
            {
                return false;
            }
        };
    }

    /**
     * @param  array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}  $dataAndIncluded
     *
     * @return array<string, mixed>
     */
    private function pageWith(array $dataAndIncluded): array
    {
        return [
            'data'     => $dataAndIncluded[0],
            'included' => $dataAndIncluded[1],
            'links'    => [],
        ];
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function personWithEmail(int $personId, ?string $email): array
    {
        $person = [
            'type'          => 'Person',
            'id'            => (string) $personId,
            'attributes'    => ['first_name' => 'Alice', 'last_name' => 'Test'],
            'relationships' => [],
        ];

        $included = [];

        if ($email !== null) {
            $person['relationships']['emails'] = [
                'data' => [['type' => 'Email', 'id' => 'em-' . $personId]],
            ];

            $included[] = [
                'type'       => 'Email',
                'id'         => 'em-' . $personId,
                'attributes' => ['address' => $email, 'primary' => true],
            ];
        }

        return [[$person], $included];
    }
}
