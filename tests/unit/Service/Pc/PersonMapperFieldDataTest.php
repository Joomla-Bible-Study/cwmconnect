<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase D coverage for {@see PersonMapper::extractFieldData()}.
 */
#[CoversClass(PersonMapper::class)]
final class PersonMapperFieldDataTest extends TestCase
{
    #[Test]
    public function extractsValueAndFieldDefinitionIdFromIncludedFieldDatum(): void
    {
        $person = $this->person([
            ['type' => 'FieldDatum', 'id' => 'fd-1'],
            ['type' => 'FieldDatum', 'id' => 'fd-2'],
        ]);

        $included = [
            $this->fieldDatum('fd-1', 'Veteran', 42),
            $this->fieldDatum('fd-2', 'Worship Team', 99),
        ];

        $mapper = new PersonMapper();
        $out    = $mapper->extractFieldData($person, $included);

        self::assertSame(
            [
                ['pc_field_id' => 42, 'value' => 'Veteran'],
                ['pc_field_id' => 99, 'value' => 'Worship Team'],
            ],
            $out,
        );
    }

    #[Test]
    public function skipsFieldDataWhosePersonHasNoFieldDataRelationship(): void
    {
        $person = $this->person([]);

        $out = new PersonMapper()->extractFieldData($person, []);

        self::assertSame([], $out);
    }

    #[Test]
    public function skipsRefsThatPointAtMissingIncludedResources(): void
    {
        $person = $this->person([['type' => 'FieldDatum', 'id' => 'fd-ghost']]);

        // Included is empty — the engine still got the page back, just
        // without that resource. Mapper should silently skip.
        $out = new PersonMapper()->extractFieldData($person, []);

        self::assertSame([], $out);
    }

    #[Test]
    public function skipsFieldDatumWithEmptyValue(): void
    {
        $person   = $this->person([['type' => 'FieldDatum', 'id' => 'fd-1']]);
        $included = [$this->fieldDatum('fd-1', '', 42)];

        $out = new PersonMapper()->extractFieldData($person, $included);

        self::assertSame([], $out);
    }

    #[Test]
    public function skipsFieldDatumMissingFieldDefinitionRelationship(): void
    {
        $person   = $this->person([['type' => 'FieldDatum', 'id' => 'fd-1']]);
        $included = [[
            'type'       => 'FieldDatum',
            'id'         => 'fd-1',
            'attributes' => ['value' => 'orphan'],
            // No relationships at all.
        ]];

        $out = new PersonMapper()->extractFieldData($person, $included);

        self::assertSame([], $out);
    }

    #[Test]
    public function castsNumericValuesToString(): void
    {
        $person   = $this->person([['type' => 'FieldDatum', 'id' => 'fd-1']]);
        $included = [$this->fieldDatum('fd-1', 2025, 42)];

        $out = new PersonMapper()->extractFieldData($person, $included);

        self::assertSame([['pc_field_id' => 42, 'value' => '2025']], $out);
    }

    /**
     * @param  list<array{type: string, id: string}>  $fieldDataRefs
     *
     * @return array<string, mixed>
     */
    private function person(array $fieldDataRefs): array
    {
        return [
            'type'          => 'Person',
            'id'            => '1',
            'attributes'    => ['first_name' => 'Alice', 'last_name' => 'Test'],
            'relationships' => $fieldDataRefs === [] ? [] : [
                'field_data' => ['data' => $fieldDataRefs],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldDatum(string $id, mixed $value, int $fieldDefId): array
    {
        return [
            'type'          => 'FieldDatum',
            'id'            => $id,
            'attributes'    => ['value' => $value],
            'relationships' => [
                'field_definition' => [
                    'data' => ['type' => 'FieldDefinition', 'id' => (string) $fieldDefId],
                ],
            ],
        ];
    }
}
