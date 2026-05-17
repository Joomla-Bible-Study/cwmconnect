<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\Exception\ApiException;
use CWM\Component\Cwmconnect\Administrator\Service\Pc\PersonMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonMapper::class)]
final class PersonMapperTest extends TestCase
{
    private PersonMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PersonMapper();
    }

    #[Test]
    public function mapsCoreIdentityFromAttributes(): void
    {
        $row = $this->mapper->map($this->person(['first_name' => 'Brent', 'last_name' => 'Cordis']));

        self::assertSame(12345, $row['pc_person_id']);
        self::assertSame('Brent Cordis', $row['name']);
        self::assertSame('Cordis', $row['lname']);
        self::assertSame('Cordis', $row['surname']);
        self::assertSame('brent-cordis-pc-12345', $row['alias']);
    }

    #[Test]
    public function aliasFallsBackToPcIdWhenNamesAreEmpty(): void
    {
        $row = $this->mapper->map($this->person([]));

        self::assertSame('pc-12345', $row['alias']);
    }

    #[Test]
    public function childFlagForcesDisplayInDirectoryToZero(): void
    {
        $row = $this->mapper->map($this->person(['first_name' => 'Junior', 'child' => true]));

        self::assertSame(0, $row['display_in_directory']);
    }

    #[Test]
    public function directoryStatusNoForcesDisplayInDirectoryToZero(): void
    {
        $row = $this->mapper->map($this->person(['directory_status' => 'no']));

        self::assertSame(0, $row['display_in_directory']);
    }

    #[Test]
    public function defaultDisplayInDirectoryIsOne(): void
    {
        $row = $this->mapper->map($this->person(['directory_status' => 'everyone']));

        self::assertSame(1, $row['display_in_directory']);
    }

    #[Test]
    public function directoryStatusMapsToScopeEnum(): void
    {
        $publicRow    = $this->mapper->map($this->person(['directory_status' => 'everyone']));
        $hiddenRow    = $this->mapper->map($this->person(['directory_status' => 'no']));
        $householdRow = $this->mapper->map($this->person(['directory_status' => 'household_only']));
        $limitedRow   = $this->mapper->map($this->person(['directory_status' => 'limited_access']));

        self::assertSame('public', $publicRow['directory_scope']);
        self::assertSame('hidden', $hiddenRow['directory_scope']);
        self::assertSame('household', $householdRow['directory_scope']);
        self::assertSame('household', $limitedRow['directory_scope']);
    }

    #[Test]
    public function sharedInfoIsEncodedAsJson(): void
    {
        $row = $this->mapper->map($this->person([
            'directory_shared_info' => ['home_address' => true, 'birthdate' => false],
        ]));

        self::assertIsString($row['pc_shared_info']);
        $decoded = json_decode($row['pc_shared_info'], true);
        self::assertTrue($decoded['home_address']);
        self::assertFalse($decoded['birthdate']);
    }

    #[Test]
    public function sharedInfoIsNullWhenAbsent(): void
    {
        $row = $this->mapper->map($this->person([]));

        self::assertNull($row['pc_shared_info']);
    }

    #[Test]
    public function birthdateIsoIsExpandedToDatetime(): void
    {
        $row = $this->mapper->map($this->person(['birthdate' => '1981-12-03']));

        self::assertSame('1981-12-03 00:00:00', $row['birthdate']);
    }

    #[Test]
    public function missingBirthdateMapsToLegacySentinel(): void
    {
        $row = $this->mapper->map($this->person([]));

        self::assertSame('0000-00-00 00:00:00', $row['birthdate']);
    }

    #[Test]
    public function picksPrimaryEmailFromIncluded(): void
    {
        $person   = $this->person([], [
            'emails' => [['type' => 'Email', 'id' => '1'], ['type' => 'Email', 'id' => '2']],
        ]);
        $included = [
            ['type' => 'Email', 'id' => '1', 'attributes' => ['address' => 'work@example.org', 'primary' => false]],
            ['type' => 'Email', 'id' => '2', 'attributes' => ['address' => 'home@example.org', 'primary' => true]],
        ];

        $row = $this->mapper->map($person, $included);

        self::assertSame('home@example.org', $row['email_to']);
    }

    #[Test]
    public function fallsBackToFirstEmailWhenNonePrimary(): void
    {
        $person   = $this->person([], [
            'emails' => [['type' => 'Email', 'id' => '7']],
        ]);
        $included = [
            ['type' => 'Email', 'id' => '7', 'attributes' => ['address' => 'fallback@example.org']],
        ];

        $row = $this->mapper->map($person, $included);

        self::assertSame('fallback@example.org', $row['email_to']);
    }

    #[Test]
    public function separatesMobileFromTelephoneByLocation(): void
    {
        $person   = $this->person([], [
            'phone_numbers' => [
                ['type' => 'PhoneNumber', 'id' => '10'],
                ['type' => 'PhoneNumber', 'id' => '11'],
            ],
        ]);
        $included = [
            ['type' => 'PhoneNumber', 'id' => '10', 'attributes' => ['number' => '555-0100', 'location' => 'Home',   'primary' => true]],
            ['type' => 'PhoneNumber', 'id' => '11', 'attributes' => ['number' => '555-0199', 'location' => 'Mobile', 'primary' => false]],
        ];

        $row = $this->mapper->map($person, $included);

        self::assertSame('555-0100', $row['telephone']);
        self::assertSame('555-0199', $row['mobile']);
    }

    #[Test]
    public function picksPrimaryAddressFromIncluded(): void
    {
        $person   = $this->person([], [
            'addresses' => [['type' => 'Address', 'id' => '20']],
        ]);
        $included = [
            [
                'type'       => 'Address',
                'id'         => '20',
                'attributes' => [
                    'street'       => '2800 Blair Blvd',
                    'city'         => 'Nashville',
                    'state'        => 'TN',
                    'country_code' => 'US',
                    'zip'          => '37212',
                    'primary'      => true,
                ],
            ],
        ];

        $row = $this->mapper->map($person, $included);

        self::assertSame('2800 Blair Blvd', $row['address']);
        self::assertSame('Nashville', $row['suburb']);
        self::assertSame('TN', $row['state']);
        self::assertSame('US', $row['country']);
        self::assertSame('37212', $row['postcode']);
    }

    #[Test]
    public function unknownIncludeReferenceIsIgnored(): void
    {
        $person   = $this->person([], [
            'emails' => [['type' => 'Email', 'id' => '999']],
        ]);
        $included = [];

        $row = $this->mapper->map($person, $included);

        self::assertSame('', $row['email_to']);
    }

    #[Test]
    public function missingIdRaisesApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->mapper->map([
            'type'       => 'Person',
            'attributes' => ['first_name' => 'X'],
        ]);
    }

    #[Test]
    public function nonPositiveIdRaisesApiException(): void
    {
        $this->expectException(ApiException::class);

        $this->mapper->map(['type' => 'Person', 'id' => '0', 'attributes' => []]);
    }

    #[Test]
    public function pcLastSyncedAtIsAlwaysSet(): void
    {
        $row = $this->mapper->map($this->person([]));

        self::assertNotEmpty($row['pc_last_synced_at']);
        self::assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $row['pc_last_synced_at'],
        );
    }

    /**
     * Build a minimal PC Person payload, merging attribute and relationship
     * overrides on top.
     *
     * @param  array<string, mixed> $attributes
     * @param  array<string, list<array{type: string, id: string}>> $relationships
     *
     * @return array<string, mixed>
     */
    private function person(array $attributes, array $relationships = []): array
    {
        $rels = [];

        foreach ($relationships as $name => $refs) {
            $rels[$name] = ['data' => $refs];
        }

        return [
            'type'          => 'Person',
            'id'            => '12345',
            'attributes'    => $attributes,
            'relationships' => $rels,
        ];
    }
}
