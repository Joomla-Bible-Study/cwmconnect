<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\Model;

use CWM\Component\Cwmconnect\Site\Model\MyprofileModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase H portal: locked-field enforcement (spec §8.3) and the
 * inverse-of-locked editable-column allowlist that drives the save bind.
 *
 * The model's DB-bound paths (loadItemByUserId, save → MemberTable) need a
 * real Joomla DatabaseInterface and are exercised by integration tests, not
 * here. These unit tests pin the pure decision logic that decides whether a
 * save is allowed and which columns make it through.
 */
#[CoversClass(MyprofileModel::class)]
final class MyprofileModelTest extends TestCase
{
    #[Test]
    public function detectLockedFieldChangesReturnsEmptyForLocalOnlyRow(): void
    {
        $item = (object) [
            'id'                  => 1,
            'pc_person_id'        => null,
            'display_in_directory' => 1,
            'name'                => 'Alice',
            'email_to'            => 'alice@example.com',
        ];

        $data = ['name' => 'Alice Edited', 'email_to' => 'alice2@example.com'];

        self::assertSame([], MyprofileModel::detectLockedFieldChanges($item, $data));
    }

    #[Test]
    public function detectLockedFieldChangesIgnoresUntouchedLockedColumns(): void
    {
        $item = (object) [
            'id'                  => 1,
            'pc_person_id'        => 42,
            'display_in_directory' => 1,
            'name'                => 'Alice',
            'email_to'            => 'alice@example.com',
            'sortname1'           => 'A',
        ];

        // Touches only an editable column (sortname1); leaves locked columns
        // out of the payload entirely. Should be allowed.
        $data = ['sortname1' => 'AA'];

        self::assertSame([], MyprofileModel::detectLockedFieldChanges($item, $data));
    }

    #[Test]
    public function detectLockedFieldChangesAllowsLockedColumnWithUnchangedValue(): void
    {
        $item = (object) [
            'id'                  => 1,
            'pc_person_id'        => 42,
            'display_in_directory' => 1,
            'name'                => 'Alice',
            'email_to'            => 'alice@example.com',
        ];

        // Browser re-posted the readonly value; spec §8.3 only rejects
        // actual changes, not a verbatim round-trip.
        $data = ['name' => 'Alice', 'email_to' => 'alice@example.com'];

        self::assertSame([], MyprofileModel::detectLockedFieldChanges($item, $data));
    }

    #[Test]
    public function detectLockedFieldChangesReportsEveryLockedColumnTouched(): void
    {
        $item = (object) [
            'id'                  => 1,
            'pc_person_id'        => 42,
            'display_in_directory' => 1,
            'name'                => 'Alice',
            'email_to'            => 'alice@example.com',
            'telephone'           => '555-0100',
        ];

        $data = [
            'name'      => 'Mallory',         // locked → violation
            'email_to'  => 'mallory@evil.com', // locked → violation
            'telephone' => '555-0100',         // locked, unchanged → fine
            'sortname1' => 'New',              // editable → fine
        ];

        $violations = MyprofileModel::detectLockedFieldChanges($item, $data);

        sort($violations);
        self::assertSame(['email_to', 'name'], $violations);
    }

    #[Test]
    public function editableColumnsExcludesEverythingLockedForPcRows(): void
    {
        $pcItem = (object) [
            'pc_person_id'         => 42,
            'display_in_directory' => 1,
        ];

        $columns = MyprofileModel::editableColumns($pcItem);

        // Locked-set members must be absent...
        foreach (['name', 'surname', 'lname', 'email_to', 'telephone', 'mobile',
            'address', 'suburb', 'state', 'postcode', 'country',
            'birthdate', 'anniversary'] as $locked) {
            self::assertNotContains(
                $locked,
                $columns,
                "PC-linked row must not have `{$locked}` in editable columns.",
            );
        }

        // ...and local-only knobs stay editable.
        self::assertContains('display_in_directory', $columns);
        self::assertContains('sortname1', $columns);
    }

    #[Test]
    public function editableColumnsReturnsFullSetForLocalOnlyRows(): void
    {
        $localItem = (object) ['pc_person_id' => null, 'display_in_directory' => 1];

        $columns = MyprofileModel::editableColumns($localItem);

        foreach (['name', 'surname', 'email_to', 'address', 'birthdate',
            'display_in_directory', 'sortname1'] as $expected) {
            self::assertContains($expected, $columns);
        }
    }

    #[Test]
    public function editableColumnsAlsoExcludesDisplayInDirectoryWhenPcHidIt(): void
    {
        // Spec: when PC set display_in_directory=0 (child row), admin can't
        // re-enable visibility without unlinking, and neither can the member.
        $hiddenPcItem = (object) [
            'pc_person_id'         => 42,
            'display_in_directory' => 0,
        ];

        $columns = MyprofileModel::editableColumns($hiddenPcItem);

        self::assertNotContains('display_in_directory', $columns);
    }
}
