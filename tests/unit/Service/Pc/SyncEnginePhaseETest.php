<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MemberRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PhotoCacheInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PhotoCacheResult;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\UpsertOutcome;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase E: SyncEngine ↔ PhotoCache wiring + report counters.
 */
#[CoversClass(SyncEngine::class)]
final class SyncEnginePhaseETest extends TestCase
{
    #[Test]
    public function downloadedResultIncrementsCounterAndPersistsImage(): void
    {
        $client = $this->clientReturning($this->pageWith([$this->personWithAvatar(1, 'https://x/abc.png')]));
        $repo   = $this->trackingRepo();
        $cache  = $this->stubCache(new PhotoCacheResult('1.png', 'hash1', true));

        $report = new SyncEngine($client, $repo, new PersonMapper(), null, null, $cache)->run([]);

        self::assertSame(1, $report->photosDownloaded);
        self::assertSame(0, $report->photosUnchanged);
        self::assertSame(
            [['pcPersonId' => 1, 'relativePath' => '1.png', 'hash' => 'hash1']],
            $repo->imageWrites,
        );
    }

    #[Test]
    public function unchangedResultIncrementsCounterAndSkipsPersistence(): void
    {
        $client = $this->clientReturning($this->pageWith([$this->personWithAvatar(1, 'https://x/abc.png')]));
        $repo   = $this->trackingRepo();
        $cache  = $this->stubCache(new PhotoCacheResult('1.png', 'hash1', false));

        $report = new SyncEngine($client, $repo, new PersonMapper(), null, null, $cache)->run([]);

        self::assertSame(0, $report->photosDownloaded);
        self::assertSame(1, $report->photosUnchanged);
        self::assertSame([], $repo->imageWrites);
    }

    #[Test]
    public function nullCacheResultLeavesAllCountersAtZero(): void
    {
        $client = $this->clientReturning($this->pageWith([$this->personWithAvatar(1, 'https://x/abc.png')]));
        $repo   = $this->trackingRepo();
        $cache  = $this->stubCache(null);

        $report = new SyncEngine($client, $repo, new PersonMapper(), null, null, $cache)->run([]);

        self::assertSame(0, $report->photosDownloaded);
        self::assertSame(0, $report->photosUnchanged);
        self::assertSame([], $repo->imageWrites);
    }

    #[Test]
    public function personWithoutAvatarSkipsCacheEntirely(): void
    {
        $client = $this->clientReturning($this->pageWith([$this->personWithAvatar(1, null)]));
        $repo   = $this->trackingRepo();
        $cache  = $this->countingCache();

        new SyncEngine($client, $repo, new PersonMapper(), null, null, $cache)->run([]);

        self::assertSame(0, $cache->calls, 'No avatar URL must never reach the cache.');
    }

    #[Test]
    public function photoStepIsSkippedWhenNoCacheIsWired(): void
    {
        $client = $this->clientReturning($this->pageWith([$this->personWithAvatar(1, 'https://x/abc.png')]));
        $repo   = $this->trackingRepo();

        // No cache passed — Phase C/D parity.
        $report = new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(0, $report->photosDownloaded);
        self::assertSame([], $repo->imageWrites);
        self::assertTrue($report->success());
    }

    #[Test]
    public function cacheExceptionIsRecordedAndDoesNotAbortRun(): void
    {
        $client = $this->clientReturning($this->pageWith([$this->personWithAvatar(1, 'https://x/abc.png')]));
        $repo   = $this->trackingRepo();
        $cache  = new class implements PhotoCacheInterface {
            public function cache(int $pcPersonId, ?string $avatarUrl, ?string $currentHash): ?PhotoCacheResult
            {
                throw new \RuntimeException('disk full');
            }
        };

        $report = new SyncEngine($client, $repo, new PersonMapper(), null, null, $cache)->run([]);

        self::assertSame(0, $report->photosDownloaded);
        self::assertSame(1, $report->errorCount());
        self::assertFalse($report->success());
    }

    private function clientReturning(array $page): Client
    {
        return new class ($page) extends Client {
            public function __construct(private array $page) {}

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

    private function trackingRepo(): MemberRepositoryInterface
    {
        return new class implements MemberRepositoryInterface {
            /** @var list<array{pcPersonId: int, relativePath: string, hash: string}> */
            public array $imageWrites = [];

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
                $this->imageWrites[] = [
                    'pcPersonId'   => $pcPersonId,
                    'relativePath' => $relativePath,
                    'hash'         => $hash,
                ];
            }

            public function findImageHashByPcPersonId(int $pcPersonId): ?string
            {
                return null;
            }
        };
    }

    private function stubCache(?PhotoCacheResult $result): PhotoCacheInterface
    {
        return new class ($result) implements PhotoCacheInterface {
            public function __construct(private readonly ?PhotoCacheResult $result) {}

            public function cache(int $pcPersonId, ?string $avatarUrl, ?string $currentHash): ?PhotoCacheResult
            {
                return $this->result;
            }
        };
    }

    /** @return object&PhotoCacheInterface */
    private function countingCache(): object
    {
        return new class implements PhotoCacheInterface {
            public int $calls = 0;

            public function cache(int $pcPersonId, ?string $avatarUrl, ?string $currentHash): ?PhotoCacheResult
            {
                $this->calls++;

                return null;
            }
        };
    }

    /**
     * @param  list<array<string, mixed>>  $data
     *
     * @return array<string, mixed>
     */
    private function pageWith(array $data): array
    {
        return ['data' => $data, 'included' => [], 'links' => []];
    }

    /**
     * @return array<string, mixed>
     */
    private function personWithAvatar(int $personId, ?string $avatarUrl): array
    {
        return [
            'type'          => 'Person',
            'id'            => (string) $personId,
            'attributes'    => [
                'first_name' => 'Alice',
                'last_name'  => 'Test',
                'avatar'     => $avatarUrl,
            ],
            'relationships' => [],
        ];
    }
}
