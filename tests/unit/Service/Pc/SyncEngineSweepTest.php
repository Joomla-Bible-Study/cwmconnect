<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MemberRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\UpsertOutcome;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The sweep deletes the synced members a run didn't see, so it may only run
 * after a walk that reached the end of Planning Center's list.
 */
#[CoversClass(SyncEngine::class)]
final class SyncEngineSweepTest extends TestCase
{
    #[Test]
    public function aCompleteWalkSweepsTheMembersItDidNotSee(): void
    {
        $repo   = $this->repo();
        $report = new SyncEngine($this->client(pages: 3), $repo, new PersonMapper())->run([]);

        self::assertSame([1, 2, 3], $repo->swept);
        self::assertSame([], $report->errors);
    }

    #[Test]
    public function aWalkStoppedAtThePageCapDeletesNoOne(): void
    {
        $repo   = $this->repo();
        $report = new SyncEngine($this->client(pages: \PHP_INT_MAX), $repo, new PersonMapper())->run([]);

        self::assertNull($repo->swept, 'The people a capped run did not reach are still in Planning Center.');
        self::assertSame(200, $report->seen, 'Everyone it did reach is still synced.');
        self::assertCount(1, $report->errors);
        self::assertStringContainsString('nobody was deleted', $report->errors[0]['message']);
    }

    /**
     * People one to a page, person n on page n, with a next link until the last.
     */
    private function client(int $pages): Client
    {
        return new class ($pages) extends Client {
            private int $page = 0;

            public function __construct(private int $pages)
            {
                // bypass parent
            }

            public function getJson(string $path, array $query = []): array
            {
                return $this->next();
            }

            public function getJsonAbsolute(string $url): array
            {
                return $this->next();
            }

            /** @return array<string, mixed> */
            private function next(): array
            {
                $this->page++;

                return [
                    'data'     => [[
                        'type'          => 'Person',
                        'id'            => (string) $this->page,
                        'attributes'    => ['first_name' => 'Person', 'last_name' => (string) $this->page],
                        'relationships' => [],
                    ]],
                    'included' => [],
                    'links'    => $this->page < $this->pages ? ['next' => 'https://api.example.test/people?offset=' . $this->page] : [],
                ];
            }
        };
    }

    private function repo(): MemberRepositoryInterface
    {
        return new class implements MemberRepositoryInterface {
            /** @var list<int>|null The ids the sweep was given, or null if it never ran. */
            public ?array $swept = null;

            public function upsertByPcPersonId(array $attrs): UpsertOutcome
            {
                return UpsertOutcome::Added;
            }

            public function deleteMissingPcPersonIds(array $seenPcPersonIds): int
            {
                $this->swept = $seenPcPersonIds;

                return 0;
            }

            public function findIdByPcPersonId(int $pcPersonId): ?int
            {
                return 1000 + $pcPersonId;
            }

            public function updateImageByPcPersonId(int $pcPersonId, string $relativePath, string $hash): void {}

            public function findImageHashByPcPersonId(int $pcPersonId): ?string
            {
                return null;
            }
        };
    }
}
