<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Helper;

use CWM\Component\Cwmconnect\Administrator\Helper\PcLockedFields;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase F coverage for the locked-fields decision matrix.
 */
#[CoversClass(PcLockedFields::class)]
final class PcLockedFieldsTest extends TestCase
{
    #[Test]
    public function returnsEmptyListForNullItem(): void
    {
        self::assertSame([], PcLockedFields::forItem(null));
    }

    #[Test]
    public function returnsEmptyListForStandaloneMember(): void
    {
        $item = (object) ['pc_person_id' => null, 'display_in_directory' => 1];

        self::assertSame([], PcLockedFields::forItem($item));
    }

    #[Test]
    public function returnsEmptyListForPcPersonIdZero(): void
    {
        $item = (object) ['pc_person_id' => 0];

        self::assertSame([], PcLockedFields::forItem($item));
    }

    #[Test]
    public function locksBaseSetForPcLinkedMemberStillVisible(): void
    {
        $item   = (object) ['pc_person_id' => 42, 'display_in_directory' => 1];
        $locked = PcLockedFields::forItem($item);

        foreach (['name', 'email_to', 'telephone', 'address', 'birthdate', 'gender', 'image'] as $expected) {
            self::assertContains(
                $expected,
                $locked,
                \sprintf('Expected `%s` to be locked for a PC-linked member.', $expected),
            );
        }

        self::assertNotContains(
            'display_in_directory',
            $locked,
            'display_in_directory must stay editable when PC has it visible.',
        );
        self::assertNotContains('alias', $locked, 'Alias is editable per spec §6.3.');
        self::assertNotContains('con_position', $locked, 'Position is editable per spec §6.3.');
    }

    #[Test]
    public function locksDisplayInDirectoryWhenPcHidesTheRow(): void
    {
        // PC's child boolean (or directory_status=no) set the flag to 0.
        // Admin must unlink the row before re-enabling visibility.
        $item   = (object) ['pc_person_id' => 42, 'display_in_directory' => 0];
        $locked = PcLockedFields::forItem($item);

        self::assertContains('display_in_directory', $locked);
    }

    #[Test]
    public function treatsStringIdsAsIntegers(): void
    {
        // pc_person_id arrives as a string from the DB driver in some cases.
        $item = (object) ['pc_person_id' => '7', 'display_in_directory' => 1];

        self::assertNotEmpty(PcLockedFields::forItem($item));
    }
}
