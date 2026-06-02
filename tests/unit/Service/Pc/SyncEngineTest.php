<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ApiException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MemberRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\UpsertOutcome;
use Joomla\CMS\Http\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SyncEngine::class)]
final class SyncEngineTest extends TestCase
{
    #[Test]
    public function singlePageRunInsertsEveryPersonAndArchivesNothing(): void
    {
        $client = $this->fakeClient([
            $this->pcPage([$this->pcPerson(1, 'Alice'), $this->pcPerson(2, 'Bob')], null),
        ]);

        $repo = $this->fakeRepo(existingPcIds: [], outcome: UpsertOutcome::Added);

        $engine = new SyncEngine($client, $repo, new PersonMapper());
        $report = $engine->run(['Member']);

        self::assertSame(2, $report->seen);
        self::assertSame(2, $report->added);
        self::assertSame(0, $report->updated);
        self::assertSame(0, $report->deleted);
        self::assertTrue($report->success());
    }

    #[Test]
    public function followsPagesUntilNextLinkIsNull(): void
    {
        $client = $this->fakeClient([
            $this->pcPage([$this->pcPerson(1, 'Page1')], 'https://api.planningcenteronline.com/people/v2/people?offset=1'),
            $this->pcPage([$this->pcPerson(2, 'Page2')], null),
        ]);

        $repo = $this->fakeRepo(outcome: UpsertOutcome::Added);

        $engine = new SyncEngine($client, $repo, new PersonMapper());
        $report = $engine->run([]);

        self::assertSame(2, $report->seen);
        self::assertSame(2, $report->added);
        self::assertCount(2, $client->urls);
    }

    #[Test]
    public function updatedRowIsCountedSeparatelyFromAdded(): void
    {
        $client = $this->fakeClient([
            $this->pcPage([$this->pcPerson(1, 'Alice')], null),
        ]);

        $repo = $this->fakeRepo(existingPcIds: [1], outcome: UpsertOutcome::Updated);

        $report = new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(0, $report->added);
        self::assertSame(1, $report->updated);
    }

    #[Test]
    public function unarchivedOutcomeBumpsItsOwnCounter(): void
    {
        $client = $this->fakeClient([
            $this->pcPage([$this->pcPerson(1, 'ReturnedHome')], null),
        ]);

        $repo = $this->fakeRepo(outcome: UpsertOutcome::Unarchived);

        $report = new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(0, $report->added);
        self::assertSame(0, $report->updated);
        self::assertSame(1, $report->unarchived);
    }

    #[Test]
    public function deletedCountReflectsSweepStepResult(): void
    {
        $client = $this->fakeClient([
            $this->pcPage([$this->pcPerson(1, 'Alice')], null),
        ]);

        $repo                 = $this->fakeRepo(outcome: UpsertOutcome::Updated);
        $repo->deleteResult  = 3;

        $report = new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(3, $report->deleted);
        self::assertSame([1], $repo->capturedSeenIds);
    }

    #[Test]
    public function firstPageRequestsActiveMembersOnly(): void
    {
        $client = $this->fakeClient([
            $this->pcPage([$this->pcPerson(1, 'Alice')], null),
        ]);

        new SyncEngine($client, $this->fakeRepo(), new PersonMapper())->run([]);

        // Inactive members must never be fetched — they drop out of the result
        // set so the sweep hard-deletes their local row (drop-inactive policy).
        self::assertSame('active', $client->capturedQueries[0]['where[status]'] ?? null);
    }

    #[Test]
    public function mapperFailureRecordsErrorButContinuesRun(): void
    {
        // The second person has no id — PersonMapper throws ApiException.
        $client = $this->fakeClient([
            $this->pcPage([$this->pcPerson(1, 'Good'), ['type' => 'Person', 'attributes' => []]], null),
        ]);

        $repo = $this->fakeRepo(outcome: UpsertOutcome::Added);

        $report = new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(2, $report->seen);
        self::assertSame(1, $report->added);
        self::assertSame(1, $report->errorCount());
        self::assertFalse($report->success());
    }

    #[Test]
    public function startupFailureBubblesUp(): void
    {
        $client = new class extends Client {
            public function __construct()
            {
                // bypass parent constructor — never used in this test
            }

            public function getJson(string $path, array $query = []): array
            {
                throw new ApiException('auth failed', 401);
            }

            public function getJsonAbsolute(string $url): array
            {
                throw new ApiException('auth failed', 401);
            }
        };

        $repo = $this->fakeRepo(outcome: UpsertOutcome::Added);

        $this->expectException(ApiException::class);

        new SyncEngine($client, $repo, new PersonMapper())->run([]);
    }

    #[Test]
    public function singleMembershipStatusIsSentAsServerFilter(): void
    {
        $client = $this->fakeClient([
            $this->pcPage([], null),
        ]);

        $repo = $this->fakeRepo();

        new SyncEngine($client, $repo, new PersonMapper())->run(['Member']);

        self::assertSame('Member', $client->capturedQueries[0]['where[membership]'] ?? null);
    }

    #[Test]
    public function multipleMembershipStatusesAreNotSentAsServerFilter(): void
    {
        // PC's where[membership] accepts a single value, so the engine omits
        // the server-side filter for multiple statuses and filters in PHP
        // instead (SyncEngine::run() local-filter branch).
        $client = $this->fakeClient([
            $this->pcPage([], null),
        ]);

        $repo = $this->fakeRepo();

        new SyncEngine($client, $repo, new PersonMapper())->run(['Member', 'Regular Attender']);

        self::assertArrayNotHasKey('where[membership]', $client->capturedQueries[0]);
    }

    #[Test]
    public function engineAlwaysCallsSweepStepWithSeenIds(): void
    {
        // Engine contract: every run calls the delete sweep exactly once,
        // passing the (de-duplicated) list of PC ids it saw. Whether to no-op
        // on an empty list is the repository's decision, not the engine's.
        $client = $this->fakeClient([
            $this->pcPage([], null),
        ]);

        $repo = $this->fakeRepo();

        new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(1, $repo->deleteCalls);
        self::assertSame([], $repo->capturedSeenIds);
    }

    /**
     * Build a fake Client that yields the given pages in order.
     *
     * @param  list<array<string, mixed>> $pages
     */
    private function fakeClient(array $pages): Client
    {
        return new class ($pages) extends Client {
            /** @var list<array<string, mixed>> */
            private array $pages;

            /** @var int */
            private int $index = 0;

            /** @var list<string> */
            public array $urls = [];

            /** @var list<array<string, string>> */
            public array $capturedQueries = [];

            /** @param list<array<string, mixed>> $pages */
            public function __construct(array $pages)
            {
                // bypass parent constructor — we never touch http/token
                $this->pages = $pages;
            }

            public function getJson(string $path, array $query = []): array
            {
                $this->urls[]            = $path;
                $this->capturedQueries[] = $query;

                return $this->nextPage();
            }

            public function getJsonAbsolute(string $url): array
            {
                $this->urls[] = $url;

                return $this->nextPage();
            }

            /**
             * @return array<string, mixed>
             */
            private function nextPage(): array
            {
                if (!isset($this->pages[$this->index])) {
                    return ['data' => [], 'included' => [], 'links' => []];
                }

                return $this->pages[$this->index++];
            }
        };
    }

    /**
     * Build a fake MemberRepository that returns a fixed outcome.
     *
     * @param  list<int>      $existingPcIds   PC ids the repo pretends to have
     *                                          (cosmetic; reused if the engine
     *                                          ever calls findExisting).
     * @param  UpsertOutcome  $outcome         Outcome to return for every upsert.
     */
    private function fakeRepo(array $existingPcIds = [], UpsertOutcome $outcome = UpsertOutcome::Added): object
    {
        return new class ($outcome) implements MemberRepositoryInterface {
            public int $deleteCalls = 0;

            public int $deleteResult = 0;

            /** @var list<int> */
            public array $capturedSeenIds = [];

            public function __construct(private UpsertOutcome $outcome) {}

            public function upsertByPcPersonId(array $attrs): UpsertOutcome
            {
                return $this->outcome;
            }

            public function deleteMissingPcPersonIds(array $seenPcPersonIds): int
            {
                $this->deleteCalls++;
                $this->capturedSeenIds = $seenPcPersonIds;

                return $this->deleteResult;
            }

            public function findIdByPcPersonId(int $pcPersonId): ?int
            {
                // Deterministic test id; engine only uses this for field writes,
                // which the Phase C-style tests never request.
                return 1000 + $pcPersonId;
            }

            public function updateImageByPcPersonId(int $pcPersonId, string $relativePath, string $hash): void
            {
                // No-op stub — Phase C-style tests run without a photo cache,
                // so the engine never calls this method on them.
            }

            public function findImageHashByPcPersonId(int $pcPersonId): ?string
            {
                return null;
            }
        };
    }

    /**
     * @param  list<array<string, mixed>> $data
     * @param  string|null                $nextUrl
     *
     * @return array<string, mixed>
     */
    private function pcPage(array $data, ?string $nextUrl): array
    {
        return [
            'data'     => $data,
            'included' => [],
            'links'    => $nextUrl !== null ? ['next' => $nextUrl] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function pcPerson(int $id, string $firstName): array
    {
        return [
            'type'          => 'Person',
            'id'            => (string) $id,
            'attributes'    => ['first_name' => $firstName, 'last_name' => 'Test'],
            'relationships' => [],
        ];
    }
}
