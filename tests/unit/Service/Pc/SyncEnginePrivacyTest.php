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
use CWM\Component\Cwmconnect\Administrator\Service\Privacy\PrivacySuppressionsInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * People removed under a privacy request: the sync writes nothing from
 * Planning Center for them, keeps their blanked row through the sweep, and
 * restores them once an administrator has released them.
 *
 * Planning Center returns two people, 1 and 2, in every test.
 */
#[CoversClass(SyncEngine::class)]
final class SyncEnginePrivacyTest extends TestCase
{
    #[Test]
    public function aSuppressedPersonIsSkippedButCountedAsSeen(): void
    {
        $repo         = $this->repo();
        $suppressions = $this->suppressions([1 => false]);

        $report = $this->engine($repo, $suppressions)->run([]);

        self::assertSame([2], $repo->upserted);
        self::assertSame([1, 2], $repo->swept, 'Still seen, so the sweep keeps their blanked row.');
        self::assertSame([1], $suppressions->placeholders);
        self::assertSame([], $suppressions->restored);
        self::assertSame(1, $report->suppressed);
        self::assertSame(1, $report->added);
    }

    #[Test]
    public function aReleasedPersonIsRefilledAndRestored(): void
    {
        $repo         = $this->repo();
        $suppressions = $this->suppressions([1 => true]);

        $report = $this->engine($repo, $suppressions)->run([]);

        self::assertSame([1, 2], $repo->upserted);
        self::assertSame([1], $suppressions->restored);
        self::assertSame([], $suppressions->placeholders);
        self::assertSame(1, $report->restored);
        self::assertSame(0, $report->suppressed);
    }

    #[Test]
    public function withoutSuppressionsEveryoneIsSynced(): void
    {
        $repo = $this->repo();

        $report = new SyncEngine($this->client(), $repo, new PersonMapper())->run([]);

        self::assertSame([1, 2], $repo->upserted);
        self::assertSame(0, $report->suppressed);
    }

    #[Test]
    public function thePhotoPassSkipsASuppressedPerson(): void
    {
        $photos = $this->photoCache();
        $engine = new SyncEngine(
            $this->client(),
            $this->repo(),
            new PersonMapper(),
            null,
            null,
            $photos,
            suppressions: $this->suppressions([1 => false]),
        );

        $engine->runPhotoPass(60.0);

        self::assertSame([2], $photos->cached);
    }

    private function engine(MemberRepositoryInterface $repo, PrivacySuppressionsInterface $suppressions): SyncEngine
    {
        return new SyncEngine($this->client(), $repo, new PersonMapper(), suppressions: $suppressions);
    }

    private function client(): Client
    {
        $person = static fn(int $id): array => [
            'type'          => 'Person',
            'id'            => (string) $id,
            'attributes'    => ['first_name' => 'Person', 'last_name' => (string) $id, 'avatar' => 'https://example.test/' . $id . '.jpg'],
            'relationships' => [],
        ];

        return new class (['data' => [$person(1), $person(2)], 'included' => [], 'links' => []]) extends Client {
            /** @param array<string, mixed> $page */
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

    private function repo(): MemberRepositoryInterface
    {
        return new class implements MemberRepositoryInterface {
            /** @var list<int> */
            public array $upserted = [];

            /** @var list<int> */
            public array $swept = [];

            public function upsertByPcPersonId(array $attrs): UpsertOutcome
            {
                $this->upserted[] = (int) $attrs['pc_person_id'];

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

    /**
     * @param  array<int, bool>  $states  Planning Center person id => released.
     */
    private function suppressions(array $states): PrivacySuppressionsInterface
    {
        return new class ($states) implements PrivacySuppressionsInterface {
            /** @var list<int> */
            public array $placeholders = [];

            /** @var list<int> */
            public array $restored = [];

            /** @param array<int, bool> $states */
            public function __construct(private array $states) {}

            public function all(): array
            {
                return $this->states;
            }

            public function suppress(int $pcPersonId): void {}

            public function releaseMembers(array $memberIds): int
            {
                return 0;
            }

            public function unreleaseMembers(array $memberIds): void {}

            public function ensurePlaceholder(int $pcPersonId): void
            {
                $this->placeholders[] = $pcPersonId;
            }

            public function completeRestore(int $pcPersonId): void
            {
                $this->restored[] = $pcPersonId;
            }
        };
    }

    private function photoCache(): PhotoCacheInterface
    {
        return new class implements PhotoCacheInterface {
            /** @var list<int> */
            public array $cached = [];

            public function cache(int $pcPersonId, ?string $avatarUrl, ?string $currentHash): ?PhotoCacheResult
            {
                $this->cached[] = $pcPersonId;

                return null;
            }
        };
    }
}
