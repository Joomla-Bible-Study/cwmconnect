<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Visibility;

use CWM\Component\Cwmconnect\Administrator\Service\Visibility\DirectoryPrivacy;
use CWM\Component\Cwmconnect\Administrator\Service\Visibility\DirectoryViewer;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Members' choices applied to what a viewer is about to see.
 */
#[CoversClass(DirectoryPrivacy::class)]
final class DirectoryPrivacyTest extends TestCase
{
    /**
     * A database whose households' stored choices are these rows.
     *
     * @param   list<array{0: int, 1: string}>  $choices  [household, hidden_fields] pairs.
     */
    private function privacy(array $choices, bool $queried = true): DirectoryPrivacy
    {
        $query = $this->createStub(QueryInterface::class);

        foreach (['select', 'from', 'where', 'whereIn'] as $method) {
            $query->method($method)->willReturnSelf();
        }

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('quoteName')->willReturnArgument(0);
        $db->expects($queried ? self::atLeastOnce() : self::never())->method('createQuery')->willReturn($query);
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadObjectList')->willReturn(array_map(
            static fn(array $row): object => (object) ['funitid' => $row[0], 'hidden_fields' => $row[1]],
            $choices,
        ));

        return new DirectoryPrivacy($db);
    }

    private static function member(int $id, int $household, string $hidden = ''): object
    {
        return (object) [
            'id'            => $id,
            'funitid'       => $household,
            'hidden_fields' => $hidden,
            'email_to'      => "m{$id}@example.com",
            'telephone'     => "555-01{$id}",
            'address'       => "{$id} Elm St",
        ];
    }

    #[Test]
    public function aHouseholdsSharedDetailIsHiddenOnEveryoneInIt(): void
    {
        $ann = self::member(1, 3, 'email,address');
        $bob = self::member(2, 3);
        $cal = self::member(9, 4);

        $this->privacy([[3, 'email,address']])->apply([$ann, $bob, $cal], new DirectoryViewer(memberIds: [9], householdIds: [4]));

        self::assertSame(['', ''], [$ann->email_to, $ann->address], "Ann's own choices.");
        self::assertSame('555-011', $ann->telephone);
        self::assertSame(['m2@example.com', ''], [$bob->email_to, $bob->address], "Bob shares Ann's address, not her email.");
        self::assertSame('9 Elm St', $cal->address, 'The viewer sees all of themselves.');
        self::assertFalse(isset($cal->hidden_details));
    }

    #[Test]
    public function theHouseholdAndLeadershipSeeEverything(): void
    {
        $ann = self::member(1, 3, 'email,address');

        $this->privacy([], queried: false)->apply([$ann], new DirectoryViewer(memberIds: [2], householdIds: [3]));
        self::assertSame('m1@example.com', $ann->email_to, 'Her household, without asking the database.');

        $this->privacy([], queried: false)->apply([$ann], new DirectoryViewer(seesAll: true));
        self::assertSame('1 Elm St', $ann->address, 'Church leadership, nor for them.');
    }

    #[Test]
    public function aHouseholdPhotoIsHiddenWhenAnyoneInItHidesTheirs(): void
    {
        $privacy = $this->privacy([[3, 'email'], [3, 'photo'], [5, 'address']]);

        self::assertSame([3], $privacy->hiddenHouseholdPhotos([3, 5], new DirectoryViewer()));
        self::assertSame([], $privacy->hiddenHouseholdPhotos([3, 5], new DirectoryViewer(householdIds: [3])), 'Not from the household itself.');
        self::assertSame([], $this->privacy([], queried: false)->hiddenHouseholdPhotos([3], new DirectoryViewer(seesAll: true)));
    }

    #[Test]
    public function whatEveryoneShareIsLeftWithoutTheHiddenDetails(): void
    {
        $bob = self::member(2, 3, 'mobile');

        self::assertSame(['phone', 'mobile'], $this->privacy([[3, 'phone,photo'], [3, 'mobile']])->hiddenFromMembers($bob));
    }
}
