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
    public function capturesPcMembershipDesignation(): void
    {
        $member   = $this->mapper->map($this->person(['membership' => 'Member']));
        $attender = $this->mapper->map($this->person(['membership' => 'Regular Attender']));
        $blank    = $this->mapper->map($this->person([]));

        self::assertSame('Member', $member['pc_membership']);
        self::assertSame('Regular Attender', $attender['pc_membership']);
        self::assertSame('', $blank['pc_membership']);
    }

    #[Test]
    public function capturesGenderVerbatimFromPc(): void
    {
        self::assertSame('Male', $this->mapper->map($this->person(['gender' => 'Male']))['gender']);
        self::assertSame('Female', $this->mapper->map($this->person(['gender' => 'Female']))['gender']);
        self::assertSame('', $this->mapper->map($this->person([]))['gender']);
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
    public function fullDirectoryListsEveryActiveMemberRegardlessOfPcPreference(): void
    {
        // Show-all policy: PC `directory_status` is mostly an unset default,
        // not a deliberate opt-out, so it never hides anyone. Children are
        // listed too. Only inactive membership keeps a row off the directory.
        foreach (['participant', 'viewer', 'no_access', ''] as $status) {
            $row = $this->mapper->map($this->person([
                'directory_status' => $status,
                'status'           => 'active',
            ]));

            self::assertSame(1, $row['display_in_directory'], "directory_status='$status' must still list");
            self::assertSame('public', $row['directory_scope'], "directory_status='$status' scope");
            self::assertSame('', $row['hidden_reason'], "directory_status='$status' reason");
        }
    }

    #[Test]
    public function childrenAreListedInTheFullDirectory(): void
    {
        $row = $this->mapper->map($this->person([
            'first_name'       => 'Junior',
            'child'            => true,
            'directory_status' => 'no_access',
            'status'           => 'active',
        ]));

        self::assertSame(1, $row['display_in_directory']);
        self::assertSame('', $row['hidden_reason']);
    }

    #[Test]
    public function inactiveMembershipIsTheOnlySyncedHiddenReason(): void
    {
        // Inactive members are still imported but unpublished; the admin list
        // surfaces "inactive" as the reason. (A child / no_access person who is
        // active is fully listed under the show-all policy.)
        $inactive = $this->mapper->map($this->person([
            'status'           => 'inactive',
            'child'            => true,
            'directory_status' => 'participant',
        ]));

        self::assertSame(0, $inactive['published']);
        self::assertSame('inactive', $inactive['hidden_reason']);

        $active = $this->mapper->map($this->person(['status' => 'active', 'directory_status' => 'no_access']));
        self::assertSame(1, $active['published']);
        self::assertSame('', $active['hidden_reason']);
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
    public function extractHouseholdMapsTheIncludedHouseholdResource(): void
    {
        $person   = $this->person(['first_name' => 'Brent'], ['households' => [['type' => 'Household', 'id' => '13638556']]]);
        $included = [
            ['type' => 'Household', 'id' => '13638556', 'attributes' => ['name' => 'Cordis Household']],
        ];

        $household = $this->mapper->extractHousehold($person, $included);

        self::assertSame(13638556, $household['pc_household_id']);
        self::assertSame('Cordis Household', $household['name']);
        self::assertSame('cordis-household-pchh-13638556', $household['alias']);
    }

    #[Test]
    public function extractHouseholdIsNullWhenPersonHasNoHousehold(): void
    {
        $person = $this->person(['first_name' => 'Solo']);

        self::assertNull($this->mapper->extractHousehold($person, []));
    }

    #[Test]
    public function extractHouseholdIsNullWhenResourceNotIncluded(): void
    {
        // Household ref present but the Household resource didn't land in the
        // page's `included` — we link on the next page that carries it.
        $person = $this->person(['first_name' => 'Brent'], ['households' => [['type' => 'Household', 'id' => '13638556']]]);

        self::assertNull($this->mapper->extractHousehold($person, []));
    }

    #[Test]
    public function extractHouseholdFallsBackToAGeneratedNameWhenBlank(): void
    {
        $person   = $this->person(['first_name' => 'Brent'], ['households' => [['type' => 'Household', 'id' => '42']]]);
        $included = [['type' => 'Household', 'id' => '42', 'attributes' => ['name' => '']]];

        $household = $this->mapper->extractHousehold($person, $included);

        self::assertSame('Household 42', $household['name']);
        self::assertSame('household-42-pchh-42', $household['alias']);
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
