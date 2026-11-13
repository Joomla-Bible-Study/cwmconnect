<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\CustomFieldWriterInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\FieldMapRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MemberRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\UpsertOutcome;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase D: SyncEngine ↔ FieldMapRepository ↔ CustomFieldWriter wiring.
 */
#[CoversClass(SyncEngine::class)]
final class SyncEnginePhaseDTest extends TestCase
{
    #[Test]
    public function customFieldsAreWrittenViaTheWriterWhenAMappingExists(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithFieldData(1, 'fd-1', 'Veteran', 42)));
        $repo    = $this->stubRepo();
        $mapRepo = $this->stubMapRepo([
            42 => $this->mappingRow(42, 7777),
        ]);
        $writer  = $this->stubWriter(true);

        $report = new SyncEngine($client, $repo, new PersonMapper(), $mapRepo, $writer)->run([]);

        self::assertSame(1, $report->customFieldsWritten);
        self::assertSame([['memberId' => 1001, 'values' => [7777 => ['Veteran']]]], $writer->captured);
    }

    #[Test]
    public function everyCheckedOptionIsWrittenTogether(): void
    {
        $client  = $this->clientReturning($this->pageOf($this->person(1, [[42, 'Choir'], [42, 'Ushers']])));
        $mapRepo = $this->stubMapRepo([42 => $this->mappingRow(42, 7777)]);
        $writer  = $this->stubWriter(true);

        $report = new SyncEngine($client, $this->stubRepo(), new PersonMapper(), $mapRepo, $writer)->run([]);

        self::assertSame([['memberId' => 1001, 'values' => [7777 => ['Choir', 'Ushers']]]], $writer->captured);
        self::assertSame(1, $report->customFieldsWritten, 'One field, however many options.');
    }

    #[Test]
    public function aMappedFieldThePersonHasNoValueForIsCleared(): void
    {
        $client  = $this->clientReturning($this->pageOf($this->person(1, [[42, 'Veteran']]), $this->person(2, [])));
        $mapRepo = $this->stubMapRepo([42 => $this->mappingRow(42, 7777), 43 => $this->mappingRow(43, 8888)]);
        $writer  = $this->stubWriter(true);

        new SyncEngine($client, $this->stubRepo(), new PersonMapper(), $mapRepo, $writer)->run([]);

        self::assertSame([
            ['memberId' => 1001, 'values' => [7777 => ['Veteran'], 8888 => []]],
            ['memberId' => 1002, 'values' => [7777 => [], 8888 => []]],
        ], $writer->captured);
    }

    #[Test]
    public function aPageWithNoFieldDataClearsNothing(): void
    {
        $client  = $this->clientReturning($this->pageOf($this->person(1, []), $this->person(2, [])));
        $mapRepo = $this->stubMapRepo([42 => $this->mappingRow(42, 7777)]);
        $writer  = $this->stubWriter(true);

        $report = new SyncEngine($client, $this->stubRepo(), new PersonMapper(), $mapRepo, $writer)->run([]);

        self::assertSame([], $writer->captured, 'More likely a response without field data than people with none.');
        self::assertTrue($report->success());
    }

    #[Test]
    public function fieldsWithoutMappingsAreSilentlyDropped(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithFieldData(1, 'fd-1', 'Unmapped', 99)));
        $repo    = $this->stubRepo();
        $mapRepo = $this->stubMapRepo([]);  // no mappings at all
        $writer  = $this->stubWriter(true);

        $report = new SyncEngine($client, $repo, new PersonMapper(), $mapRepo, $writer)->run([]);

        self::assertSame(0, $report->customFieldsWritten);
        self::assertSame([], $writer->captured);
        self::assertTrue($report->success());
    }

    #[Test]
    public function customFieldSyncIsSkippedEntirelyWhenNoMappingRepoIsWired(): void
    {
        $client = $this->clientReturning($this->pageWith($this->personWithFieldData(1, 'fd-1', 'Anything', 42)));
        $repo   = $this->stubRepo();
        $writer = $this->stubWriter(true);

        // Constructor still accepts null map repo + null writer (Phase C parity).
        $report = new SyncEngine($client, $repo, new PersonMapper())->run([]);

        self::assertSame(0, $report->customFieldsWritten);
        // The writer was never even consulted.
        self::assertSame([], $writer->captured);
        self::assertTrue($report->success());
    }

    #[Test]
    public function writerExceptionIsCapturedAndDoesNotAbortRun(): void
    {
        $client  = $this->clientReturning($this->pageOf($this->person(1, [[42, 'Boom']]), $this->person(2, [[42, 'Fine']])));
        $mapRepo = $this->stubMapRepo([42 => $this->mappingRow(42, 7777)]);
        $writer  = $this->stubWriterThatThrowsFor(1001);

        $report = new SyncEngine($client, $this->stubRepo(), new PersonMapper(), $mapRepo, $writer)->run([]);

        self::assertSame(1, $report->customFieldsWritten, 'The next person is still written.');
        self::assertSame(1, $report->errorCount());
        self::assertStringContainsString('Custom-field write failed', $report->errors[0]['message']);
        self::assertSame(2, $report->added);
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
                // Phase D tests don't wire a photo cache; this is unreachable.
            }

            public function findImageHashByPcPersonId(int $pcPersonId): ?string
            {
                return null;
            }
        };
    }

    /**
     * @param  array<int, array{id: int, pc_field_id: int, pc_field_slug: string, pc_field_name: string, joomla_field_id: int}>  $rows
     */
    private function stubMapRepo(array $rows): FieldMapRepositoryInterface
    {
        return new class ($rows) implements FieldMapRepositoryInterface {
            /** @param array<int, array{id: int, pc_field_id: int, pc_field_slug: string, pc_field_name: string, joomla_field_id: int}> $rows */
            public function __construct(private array $rows) {}

            public function allKeyedByPcFieldId(): array
            {
                return $this->rows;
            }

            public function lockedJoomlaFieldNames(): array
            {
                // Phase D tests don't exercise the form-lock path; return
                // an empty list so the engine never tries to apply a lock.
                return [];
            }
        };
    }

    /**
     * @param  bool  $changes  Whether every field it's given changes.
     *
     * @return object&CustomFieldWriterInterface
     */
    private function stubWriter(bool $changes): object
    {
        return new class ($changes) implements CustomFieldWriterInterface {
            /** @var list<array{memberId: int, values: array<int, list<string>>}> */
            public array $captured = [];

            public function __construct(private bool $changes) {}

            public function writeValues(int $memberId, array $values): int
            {
                $this->captured[] = ['memberId' => $memberId, 'values' => $values];

                return $this->changes ? \count($values) : 0;
            }
        };
    }

    private function stubWriterThatThrowsFor(int $failingMemberId): CustomFieldWriterInterface
    {
        return new class ($failingMemberId) implements CustomFieldWriterInterface {
            public function __construct(private int $failingMemberId) {}

            public function writeValues(int $memberId, array $values): int
            {
                if ($memberId === $this->failingMemberId) {
                    throw new \RuntimeException('The database rejected the write');
                }

                return \count($values);
            }
        };
    }

    /**
     * @param  array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}  ...$people
     *
     * @return array<string, mixed>
     */
    private function pageOf(array ...$people): array
    {
        return [
            'data'     => array_merge(...array_column($people, 0)),
            'included' => array_merge(...array_column($people, 1)),
            'links'    => [],
        ];
    }

    /**
     * @param  list<array{0: int, 1: string}>  $values  Planning Center field id and value, one per datum.
     *
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function person(int $personId, array $values): array
    {
        $refs   = [];
        $datums = [];

        foreach ($values as $i => [$pcFieldId, $value]) {
            $id       = 'fd-' . $personId . '-' . $i;
            $refs[]   = ['type' => 'FieldDatum', 'id' => $id];
            $datums[] = [
                'type'          => 'FieldDatum',
                'id'            => $id,
                'attributes'    => ['value' => $value],
                'relationships' => ['field_definition' => ['data' => ['type' => 'FieldDefinition', 'id' => (string) $pcFieldId]]],
            ];
        }

        $person = [
            'type'          => 'Person',
            'id'            => (string) $personId,
            'attributes'    => ['first_name' => 'Person', 'last_name' => (string) $personId],
            'relationships' => ['field_data' => ['data' => $refs]],
        ];

        return [[$person], $datums];
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
    private function personWithFieldData(int $personId, string $fdId, string $value, int $pcFieldId): array
    {
        $person = [
            'type'          => 'Person',
            'id'            => (string) $personId,
            'attributes'    => ['first_name' => 'Alice', 'last_name' => 'Test'],
            'relationships' => [
                'field_data' => ['data' => [['type' => 'FieldDatum', 'id' => $fdId]]],
            ],
        ];

        $datum = [
            'type'          => 'FieldDatum',
            'id'            => $fdId,
            'attributes'    => ['value' => $value],
            'relationships' => [
                'field_definition' => ['data' => ['type' => 'FieldDefinition', 'id' => (string) $pcFieldId]],
            ],
        ];

        return [[$person], [$datum]];
    }

    /**
     * @return array{id: int, pc_field_id: int, pc_field_slug: string, pc_field_name: string, joomla_field_id: int}
     */
    private function mappingRow(int $pcFieldId, int $joomlaFieldId): array
    {
        return [
            'id'              => 1,
            'pc_field_id'     => $pcFieldId,
            'pc_field_slug'   => 'slug-' . $pcFieldId,
            'pc_field_name'   => 'PC Field ' . $pcFieldId,
            'joomla_field_id' => $joomlaFieldId,
        ];
    }
}
