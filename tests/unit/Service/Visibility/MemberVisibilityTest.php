<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Visibility;

use CWM\Component\Cwmconnect\Administrator\Service\Visibility\DirectoryViewer;
use CWM\Component\Cwmconnect\Administrator\Service\Visibility\MemberVisibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * What a member keeps from the general membership, and who sees past it.
 */
#[CoversClass(MemberVisibility::class)]
#[CoversClass(DirectoryViewer::class)]
final class MemberVisibilityTest extends TestCase
{
    #[Test]
    public function aChoiceIsStoredAsKnownKeysInAFixedOrder(): void
    {
        self::assertSame('email,photo', MemberVisibility::encode(['photo', 'email']));
        self::assertSame('email,address', MemberVisibility::encode(' address ,email'));
        self::assertSame('', MemberVisibility::encode(['nickname', '', 'EMAIL']), 'Unknown keys are dropped.');
        self::assertSame('', MemberVisibility::encode(null));
        self::assertSame(['phone', 'mobile'], MemberVisibility::parse('mobile,phone,mobile'));
    }

    #[Test]
    public function aHouseholdHidesWhatItShares(): void
    {
        self::assertSame(
            ['phone', 'address'],
            MemberVisibility::sharedHidden(['address,email', '', 'phone,photo']),
            'Only the household details count: email and photo are each person\'s own.',
        );
        self::assertSame([], MemberVisibility::sharedHidden([]));
    }

    #[Test]
    public function aMembersHiddenDetailsAreTheirOwnAndTheirHouseholds(): void
    {
        $member = (object) ['hidden_fields' => 'photo,email'];

        self::assertSame(['email', 'address', 'photo'], MemberVisibility::hiddenFor($member, ['address']));
        self::assertSame([], MemberVisibility::hiddenFor((object) []));
    }

    #[Test]
    public function scrubbingBlanksEveryColumnADetailCovers(): void
    {
        $member = (object) [
            'name'      => 'Ann Zanzibar',
            'email_to'  => 'ann@example.com',
            'telephone' => '555-0100',
            'fax'       => '555-0101',
            'mobile'    => '555-0102',
            'address'   => '9 Elm St',
            'suburb'    => 'Zanzibar',
            'state'     => 'TN',
            'postcode'  => '37212',
            'country'   => 'US',
            'lat'       => 36.1,
            'lng'       => -86.8,
            'birthdate' => '1980-05-12',
            'image'     => '123.jpg',
        ];

        MemberVisibility::scrub($member, ['phone', 'address', 'birthdate']);

        self::assertSame('Ann Zanzibar', $member->name);
        self::assertSame('ann@example.com', $member->email_to, 'Not hidden, so left alone.');
        self::assertSame('555-0102', $member->mobile);
        self::assertSame('123.jpg', $member->image);
        self::assertSame(['', ''], [$member->telephone, $member->fax]);
        self::assertSame(['', '', '', '', ''], [$member->address, $member->suburb, $member->state, $member->postcode, $member->country]);
        self::assertSame([0, 0], [$member->lat, $member->lng], 'No map position, so no pin.');
        self::assertNull($member->birthdate);
        self::assertSame(['phone', 'address', 'birthdate'], $member->hidden_details);
        self::assertFalse(property_exists($member, 'anniversary'), 'A column the row was loaded without stays absent.');
    }

    #[Test]
    public function leadershipSeesEveryoneAndEveryoneSeesThemselvesAndTheirHousehold(): void
    {
        $member   = (object) ['id' => 7, 'funitid' => 3];
        $loner    = (object) ['id' => 8, 'funitid' => 0];
        $leader   = new DirectoryViewer(seesAll: true);
        $self     = new DirectoryViewer(memberIds: [7], householdIds: [3]);
        $spouse   = new DirectoryViewer(memberIds: [9], householdIds: [3]);
        $other    = new DirectoryViewer(memberIds: [10], householdIds: [4]);
        $unlinked = new DirectoryViewer();

        self::assertTrue($leader->seesAllOf($member));
        self::assertTrue($self->seesAllOf($member));
        self::assertTrue($spouse->seesAllOf($member));
        self::assertFalse($other->seesAllOf($member));
        self::assertFalse($unlinked->seesAllOf($member));
        self::assertFalse(new DirectoryViewer(householdIds: [0])->seesAllOf($loner), 'No household is nobody\'s household.');
    }
}
