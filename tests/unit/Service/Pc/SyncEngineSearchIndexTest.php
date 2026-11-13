<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberPairingInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\Client;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\CustomFieldWriterInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\FieldMapRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\MemberRepositoryInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncEngine;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\UpsertOutcome;
use CWM\Component\Cwmconnect\Administrator\Service\Privacy\PrivacySuppressionsInterface;
use CWM\Component\Cwmconnect\Administrator\Service\Search\SearchIndexInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The sync tells Smart Search about each member it changed, once all of that
 * person's writes are done, and leaves the rest alone.
 *
 * Planning Center returns three people in every test: 1, 2 with a custom
 * field value, and 3 with an email address. Member row ids are 1000 + the
 * person id.
 */
#[CoversClass(SyncEngine::class)]
final class SyncEngineSearchIndexTest extends TestCase
{
    #[Test]
    public function addedUpdatedAndUnarchivedMembersAreIndexedAgain(): void
    {
        $index = $this->searchIndex();

        $this->engine([1 => UpsertOutcome::Added, 2 => UpsertOutcome::Updated, 3 => UpsertOutcome::Unarchived], $index)->run([]);

        self::assertSame([1001, 1002, 1003], $index->changed);
    }

    #[Test]
    public function unchangedMembersAreLeftAlone(): void
    {
        $index = $this->searchIndex();

        $report = $this->engine($this->unchanged(), $index)->run([]);

        self::assertSame([], $index->changed);
        self::assertSame(3, $report->updated, 'Still counted as updated: the sync refreshed them.');
    }

    #[Test]
    public function aChangedCustomFieldValueCountsAsAChange(): void
    {
        $index = $this->searchIndex();

        $this->engine($this->unchanged(), $index, fieldWriter: $this->fieldWriter(true))->run([]);

        self::assertSame([1002], $index->changed);
    }

    #[Test]
    public function aCustomFieldValueAlreadyStoredIsNoChange(): void
    {
        $index = $this->searchIndex();

        $this->engine($this->unchanged(), $index, fieldWriter: $this->fieldWriter(false))->run([]);

        self::assertSame([], $index->changed);
    }

    #[Test]
    public function aNewPairingCountsAsAChange(): void
    {
        $index = $this->searchIndex();

        $this->engine($this->unchanged(), $index, pairing: $this->pairing())->run([]);

        self::assertSame([1003], $index->changed, "The account's name is indexed with the member.");
    }

    #[Test]
    public function aRestoredMemberIsIndexedAgain(): void
    {
        $index = $this->searchIndex();

        $this->engine($this->unchanged(), $index, suppressions: $this->suppressions([1 => true]))->run([]);

        self::assertSame([1001], $index->changed);
    }

    #[Test]
    public function aSuppressedMemberIsNotIndexed(): void
    {
        $index    = $this->searchIndex();
        $outcomes = [1 => UpsertOutcome::Added, 2 => UpsertOutcome::Added, 3 => UpsertOutcome::Added];

        $this->engine($outcomes, $index, suppressions: $this->suppressions([1 => false]))->run([]);

        self::assertSame([1002, 1003], $index->changed);
    }

    #[Test]
    public function anIndexFailureIsReportedAndTheRunGoesOn(): void
    {
        $index    = $this->searchIndex(failFor: 1001);
        $outcomes = [1 => UpsertOutcome::Added, 2 => UpsertOutcome::Added, 3 => UpsertOutcome::Added];

        $report = $this->engine($outcomes, $index)->run([]);

        self::assertSame([1001, 1002, 1003], $index->changed);
        self::assertSame(3, $report->added);
        self::assertCount(1, $report->errors);
        self::assertStringContainsString('Smart Search update failed', $report->errors[0]['message']);
    }

    /**
     * @param  array<int, UpsertOutcome>  $outcomes  Planning Center person id => upsert outcome.
     */
    private function engine(
        array $outcomes,
        SearchIndexInterface $searchIndex,
        ?CustomFieldWriterInterface $fieldWriter = null,
        ?MemberPairingInterface $pairing = null,
        ?PrivacySuppressionsInterface $suppressions = null,
    ): SyncEngine {
        return new SyncEngine(
            $this->client(),
            $this->repo($outcomes),
            new PersonMapper(),
            fieldMapRepo: $fieldWriter === null ? null : $this->fieldMap(),
            fieldWriter: $fieldWriter,
            pairing: $pairing,
            suppressions: $suppressions,
            searchIndex: $searchIndex,
        );
    }

    /**
     * @return  array<int, UpsertOutcome>
     */
    private function unchanged(): array
    {
        return [1 => UpsertOutcome::Unchanged, 2 => UpsertOutcome::Unchanged, 3 => UpsertOutcome::Unchanged];
    }

    private function client(): Client
    {
        $person = static fn(int $id, array $relationships = []): array => [
            'type'          => 'Person',
            'id'            => (string) $id,
            'attributes'    => ['first_name' => 'Person', 'last_name' => (string) $id],
            'relationships' => $relationships,
        ];

        $page = [
            'data' => [
                $person(1),
                $person(2, ['field_data' => ['data' => [['type' => 'FieldDatum', 'id' => 'fd-2']]]]),
                $person(3, ['emails' => ['data' => [['type' => 'Email', 'id' => 'em-3']]]]),
            ],
            'included' => [
                [
                    'type'          => 'FieldDatum',
                    'id'            => 'fd-2',
                    'attributes'    => ['value' => 'Choir'],
                    'relationships' => ['field_definition' => ['data' => ['type' => 'FieldDefinition', 'id' => '77']]],
                ],
                [
                    'type'       => 'Email',
                    'id'         => 'em-3',
                    'attributes' => ['address' => 'three@example.test', 'primary' => true],
                ],
            ],
            'links' => [],
        ];

        return new class ($page) extends Client {
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

    /**
     * @param  array<int, UpsertOutcome>  $outcomes  Planning Center person id => upsert outcome.
     */
    private function repo(array $outcomes): MemberRepositoryInterface
    {
        return new class ($outcomes) implements MemberRepositoryInterface {
            /** @param array<int, UpsertOutcome> $outcomes */
            public function __construct(private array $outcomes) {}

            public function upsertByPcPersonId(array $attrs): UpsertOutcome
            {
                return $this->outcomes[(int) $attrs['pc_person_id']];
            }

            public function deleteMissingPcPersonIds(array $seenPcPersonIds): int
            {
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

    private function fieldMap(): FieldMapRepositoryInterface
    {
        return new class implements FieldMapRepositoryInterface {
            public function allKeyedByPcFieldId(): array
            {
                return [77 => ['id' => 1, 'pc_field_id' => 77, 'pc_field_slug' => 'ministry', 'pc_field_name' => 'Ministry', 'joomla_field_id' => 501]];
            }

            public function lockedJoomlaFieldNames(): array
            {
                return [];
            }
        };
    }

    /**
     * @param  bool  $changes  What every write reports: whether the stored value changed.
     */
    private function fieldWriter(bool $changes): CustomFieldWriterInterface
    {
        return new class ($changes) implements CustomFieldWriterInterface {
            public function __construct(private bool $changes) {}

            public function setFieldValue(int $memberId, int $joomlaFieldId, string $value): bool
            {
                return $this->changes;
            }
        };
    }

    private function pairing(): MemberPairingInterface
    {
        return new class implements MemberPairingInterface {
            public function findUnpairedMemberIdByEmail(string $email): ?int
            {
                return null;
            }

            public function findJoomlaUserIdByEmail(string $email): ?int
            {
                return $email === 'three@example.test' ? 42 : null;
            }

            public function recordVerifiedEmail(int $userId, string $email, string $source): void {}

            public function hasVerifiedEmail(int $userId, string $email): bool
            {
                return true;
            }

            public function forgetVerifiedEmail(int $userId): void {}

            public function pairMemberToUser(int $memberId, int $userId): bool
            {
                return true;
            }
        };
    }

    /**
     * @param  array<int, bool>  $states  Planning Center person id => released.
     */
    private function suppressions(array $states): PrivacySuppressionsInterface
    {
        return new class ($states) implements PrivacySuppressionsInterface {
            /** @param array<int, bool> $states */
            public function __construct(private array $states) {}

            public function stateOf(int $pcPersonId): ?bool
            {
                return $this->states[$pcPersonId] ?? null;
            }

            public function suppress(int $pcPersonId): void {}

            public function releaseMembers(array $memberIds): int
            {
                return 0;
            }

            public function unreleaseMembers(array $memberIds): void {}

            public function ensureRemoved(int $pcPersonId): void {}

            public function completeRestore(int $pcPersonId): void {}
        };
    }

    /**
     * @param  int  $failFor  Member id whose update throws, as an indexer failure would.
     */
    private function searchIndex(int $failFor = 0): SearchIndexInterface
    {
        return new class ($failFor) implements SearchIndexInterface {
            /** @var list<int> */
            public array $changed = [];

            public function __construct(private int $failFor) {}

            public function memberChanged(int $memberId): void
            {
                $this->changed[] = $memberId;

                if ($memberId === $this->failFor) {
                    throw new \RuntimeException('Indexer unavailable');
                }
            }

            public function membersRemoved(array $memberIds): void {}
        };
    }
}
