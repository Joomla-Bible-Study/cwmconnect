<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Admin\Table;

use CWM\Component\Cwmconnect\Administrator\Table\MemberTable;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @since __DEPLOY_VERSION__
 */
#[CoversClass(MemberTable::class)]
final class MemberTableTest extends TestCase
{
    private function createTable(?int $loadResult = null): MemberTable
    {
        $query = $this->createStub(QueryInterface::class);
        $query->method('select')->willReturnSelf();
        $query->method('from')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('bind')->willReturnSelf();

        $db = $this->createStub(DatabaseInterface::class);
        $db->method('createQuery')->willReturn($query);
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn($loadResult);
        $db->method('quoteName')->willReturnCallback(
            fn(string|array $name): string|array => \is_array($name)
                ? array_map(fn($n) => '`' . $n . '`', $name)
                : '`' . $name . '`'
        );
        $db->method('getNullDate')->willReturn('0000-00-00 00:00:00');

        $table       = new MemberTable($db);
        $table->name = 'Jane';
        $table->lname = 'Smith';
        $table->alias = 'jane-smith';
        $table->catid = 1;

        return $table;
    }

    #[Test]
    public function checkPassesWhenUserIdIsNull(): void
    {
        $table = $this->createTable();
        $table->user_id = null;

        self::assertTrue($table->check());
    }

    #[Test]
    public function checkPassesWhenUserIdIsZero(): void
    {
        $table = $this->createTable();
        $table->user_id = 0;

        $result = $table->check();

        self::assertTrue($result);
        self::assertNull($table->user_id, 'Empty user_id should be coerced to null');
    }

    #[Test]
    public function checkPassesWhenUserIdIsUniqueAcrossMembers(): void
    {
        $table = $this->createTable(loadResult: null);
        $table->id = 5;
        $table->user_id = 42;

        self::assertTrue($table->check());
    }

    #[Test]
    public function checkFailsWhenUserIdAlreadyLinkedToAnotherMember(): void
    {
        $table = $this->createTable(loadResult: 99);
        $table->id = 5;
        $table->user_id = 42;

        self::assertFalse($table->check());
        self::assertStringContainsString(
            'COM_CWMCONNECT_ERROR_USER_ALREADY_LINKED',
            $table->getError(),
        );
    }

    #[Test]
    public function checkPassesForNewRecordWithUniqueUserId(): void
    {
        $table = $this->createTable(loadResult: null);
        $table->id = 0;
        $table->user_id = 42;

        self::assertTrue($table->check());
    }

    #[Test]
    public function checkFailsForNewRecordWithDuplicateUserId(): void
    {
        $table = $this->createTable(loadResult: 99);
        $table->id = 0;
        $table->user_id = 42;

        self::assertFalse($table->check());
        self::assertStringContainsString(
            'COM_CWMCONNECT_ERROR_USER_ALREADY_LINKED',
            $table->getError(),
        );
    }

    // store() — what the edit form posts, made storable under strict SQL mode.
    // The stub's load() always finds the alias, so these run as edits (id > 0).

    #[Test]
    public function storeTurnsTheFormsNoFamilyUnitIntoZero(): void
    {
        $table          = $this->createTable();
        $table->id      = 5;
        $table->funitid = -1;
        $table->attribs = '{"familypostion":"2"}';

        self::assertTrue($table->store());
        self::assertSame(0, $table->funitid);
        self::assertSame('0', json_decode($table->attribs, true)['familypostion']);
    }

    #[Test]
    public function storeLeavesAFamilyUnitAlone(): void
    {
        $table          = $this->createTable();
        $table->id      = 5;
        $table->funitid = 7;
        $table->attribs = '{"familypostion":"2"}';

        self::assertTrue($table->store());
        self::assertSame(7, $table->funitid);
        self::assertSame('{"familypostion":"2"}', $table->attribs);
    }

    #[Test]
    public function storeTurnsEmptyDatesIntoTheNullDate(): void
    {
        $table              = $this->createTable();
        $table->id          = 5;
        $table->birthdate   = '';
        $table->anniversary = ' ';

        self::assertTrue($table->store());
        self::assertSame('0000-00-00 00:00:00', $table->birthdate);
        self::assertSame('0000-00-00 00:00:00', $table->anniversary);
    }

    #[Test]
    public function storeTurnsEmptyCoordinatesIntoZero(): void
    {
        $table      = $this->createTable();
        $table->id  = 5;
        $table->lat = '';
        $table->lng = ' ';

        self::assertTrue($table->store());
        self::assertSame('0', $table->lat);
        self::assertSame('0', $table->lng);
    }

    #[Test]
    public function storeKeepsRealValuesAndLeavesUnpostedFieldsAlone(): void
    {
        // Null means the field was not posted: turning it into a value would
        // overwrite what is stored.
        $table              = $this->createTable();
        $table->id          = 5;
        $table->birthdate   = '1990-05-04 00:00:00';
        $table->anniversary = null;
        $table->lat         = '41.878113';
        $table->lng         = null;

        self::assertTrue($table->store());
        self::assertSame('1990-05-04 00:00:00', $table->birthdate);
        self::assertNull($table->anniversary);
        self::assertSame('41.878113', $table->lat);
        self::assertNull($table->lng);
    }

    #[Test]
    public function checkLeavesNoPropertySharedWithItsQuery(): void
    {
        // The real driver keeps the last query, and its bound values, alive;
        // store() then clones the table to check the alias. A user_id still
        // shared with the query came back as the stored row's value, so a
        // newly linked user was silently dropped.
        $query = new class implements QueryInterface {
            /** @var array<string, mixed> */
            public array $bound = [];

            public function select(string|array $columns): static
            {
                return $this;
            }

            public function from(string $table): static
            {
                return $this;
            }

            public function where(string|array $conditions, string $glue = 'AND'): static
            {
                return $this;
            }

            public function update(string $table): static
            {
                return $this;
            }

            public function set(string|array $conditions, string $glue = ','): static
            {
                return $this;
            }

            public function bind(string|array $key, mixed &$value, ParameterType $dataType = ParameterType::STRING, int $length = 0): static
            {
                $this->bound[(string) $key] = &$value;

                return $this;
            }
        };

        $db = $this->createStub(DatabaseInterface::class);
        $db->method('createQuery')->willReturn($query);
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadResult')->willReturn(null);
        $db->method('quoteName')->willReturnCallback(fn(string|array $name): string|array => $name);

        $table          = new MemberTable($db);
        $table->name    = 'Jane';
        $table->catid   = 1;
        $table->id      = 5;
        $table->user_id = 42;

        self::assertTrue($table->check());

        $clone          = clone $table;
        $clone->id      = 9;
        $clone->user_id = null;

        self::assertSame(5, $table->id);
        self::assertSame(42, $table->user_id);
    }
}
