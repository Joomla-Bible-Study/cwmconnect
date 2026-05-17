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
        self::assertSame(
            [['memberId' => 1001, 'fieldId' => 7777, 'value' => 'Veteran']],
            $writer->captured,
        );
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
    public function writerExceptionIsCapturedPerFieldAndDoesNotAbortRun(): void
    {
        $client  = $this->clientReturning($this->pageWith($this->personWithFieldData(1, 'fd-1', 'Boom', 42)));
        $repo    = $this->stubRepo();
        $mapRepo = $this->stubMapRepo([42 => $this->mappingRow(42, 7777)]);
        $writer  = $this->stubWriterThatThrows();

        $report = new SyncEngine($client, $repo, new PersonMapper(), $mapRepo, $writer)->run([]);

        self::assertSame(0, $report->customFieldsWritten);
        self::assertSame(1, $report->errorCount());
        self::assertFalse($report->success());
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

            public function archiveMissingPcPersonIds(array $seenPcPersonIds): int
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
        };
    }

    /** @return object&CustomFieldWriterInterface */
    private function stubWriter(bool $ok): object
    {
        return new class ($ok) implements CustomFieldWriterInterface {
            /** @var list<array{memberId: int, fieldId: int, value: string}> */
            public array $captured = [];

            public function __construct(private bool $ok) {}

            public function setFieldValue(int $memberId, int $joomlaFieldId, string $value): bool
            {
                $this->captured[] = ['memberId' => $memberId, 'fieldId' => $joomlaFieldId, 'value' => $value];

                return $this->ok;
            }
        };
    }

    private function stubWriterThatThrows(): CustomFieldWriterInterface
    {
        return new class implements CustomFieldWriterInterface {
            public function setFieldValue(int $memberId, int $joomlaFieldId, string $value): bool
            {
                throw new \RuntimeException('FieldsHelper rejected the write');
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
