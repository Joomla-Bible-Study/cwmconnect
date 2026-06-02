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
    public function nameIncludesMiddleNameWhenPresent(): void
    {
        $row = $this->mapper->map($this->person([
            'first_name'  => 'John',
            'middle_name' => 'Michael',
            'last_name'   => 'Smith',
        ]));

        self::assertSame('John Michael Smith', $row['name']);
        // Alias stays first+last so it doesn't churn when a middle name lands.
        self::assertSame('john-smith-pc-12345', $row['alias']);
    }

    #[Test]
    public function nameAppendsDistinctNicknameInParentheses(): void
    {
        $row = $this->mapper->map($this->person([
            'first_name' => 'Robert',
            'last_name'  => 'Jones',
            'nickname'   => 'Bob',
        ]));

        self::assertSame('Robert Jones (Bob)', $row['name']);
    }

    #[Test]
    public function nameDropsNicknameThatEchoesFirstName(): void
    {
        $row = $this->mapper->map($this->person([
            'first_name' => 'Sam',
            'last_name'  => 'Lee',
            'nickname'   => 'sam',
        ]));

        self::assertSame('Sam Lee', $row['name']);
    }

    #[Test]
    public function nameGraftsGenerationalSuffixFromComputedName(): void
    {
        // PC has no suffix field — it only surfaces in the computed `name`
        // as a trailing ", III". Graft it onto the structured parts.
        $row = $this->mapper->map($this->person([
            'first_name' => 'Sherman',
            'last_name'  => 'Cox',
            'name'       => 'Sherman Cox, III',
        ]));

        self::assertSame('Sherman Cox, III', $row['name']);
        // Alias ignores the suffix so generational siblings stay distinct only
        // by their PC id, not a churning alias.
        self::assertSame('sherman-cox-pc-12345', $row['alias']);
    }

    #[Test]
    public function nameAcceptsJrAndSrSuffixes(): void
    {
        foreach (['Jr.' => 'Jr', 'Sr' => 'Sr'] as $computed => $expected) {
            $row = $this->mapper->map($this->person([
                'first_name' => 'Sam',
                'last_name'  => 'Lee',
                'name'       => "Sam Lee, $computed",
            ]));

            self::assertSame("Sam Lee, $expected", $row['name'], "suffix '$computed'");
        }
    }

    #[Test]
    public function nameIgnoresNonSuffixCommaSegments(): void
    {
        // A "Last, First" computed format must not be mistaken for a suffix.
        $row = $this->mapper->map($this->person([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'name'       => 'Doe, Jane',
        ]));

        self::assertSame('Jane Doe', $row['name']);
    }

    #[Test]
    public function aliasFallsBackToPcIdWhenNamesAreEmpty(): void
    {
        $row = $this->mapper->map($this->person([]));

        self::assertSame('pc-12345', $row['alias']);
    }

    #[Test]
    public function childFlagForcesDisplayInDirectoryToZeroEvenWhenParticipant(): void
    {
        $row = $this->mapper->map($this->person([
            'first_name'       => 'Junior',
            'child'            => true,
            'directory_status' => 'participant',
        ]));

        self::assertSame(0, $row['display_in_directory']);
    }

    #[Test]
    public function nonParticipantDirectoryStatusKeepsDisplayInDirectoryZero(): void
    {
        // PC's real values: only `participant` is publicly listed; `viewer`
        // (can browse but isn't listed) and `no_access` stay hidden.
        foreach (['viewer', 'no_access', ''] as $status) {
            $row = $this->mapper->map($this->person(['directory_status' => $status]));

            self::assertSame(0, $row['display_in_directory'], "directory_status='$status' must not be listed");
        }
    }

    #[Test]
    public function participantDirectoryStatusEnablesDisplayInDirectory(): void
    {
        $row = $this->mapper->map($this->person(['directory_status' => 'participant']));

        self::assertSame(1, $row['display_in_directory']);
    }

    #[Test]
    public function directoryStatusMapsToScopeEnum(): void
    {
        $publicRow   = $this->mapper->map($this->person(['directory_status' => 'participant']));
        $viewerRow   = $this->mapper->map($this->person(['directory_status' => 'viewer']));
        $noAccessRow = $this->mapper->map($this->person(['directory_status' => 'no_access']));

        self::assertSame('public', $publicRow['directory_scope']);
        self::assertSame('hidden', $viewerRow['directory_scope']);
        self::assertSame('hidden', $noAccessRow['directory_scope']);
    }

    #[Test]
    public function hiddenReasonIsEmptyForListedParticipant(): void
    {
        $row = $this->mapper->map($this->person([
            'directory_status' => 'participant',
            'status'           => 'active',
        ]));

        self::assertSame('', $row['hidden_reason']);
    }

    #[Test]
    public function hiddenReasonReflectsTheBlockingGate(): void
    {
        $cases = [
            // inactive membership trumps everything, even a participant child.
            'inactive'  => ['status' => 'inactive', 'child' => true, 'directory_status' => 'participant'],
            'child'     => ['status' => 'active', 'child' => true, 'directory_status' => 'participant'],
            'viewer'    => ['status' => 'active', 'directory_status' => 'viewer'],
            'no_access' => ['status' => 'active', 'directory_status' => 'no_access'],
        ];

        foreach ($cases as $expected => $attrs) {
            $row = $this->mapper->map($this->person($attrs));

            self::assertSame($expected, $row['hidden_reason'], "expected reason '$expected'");
        }
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
