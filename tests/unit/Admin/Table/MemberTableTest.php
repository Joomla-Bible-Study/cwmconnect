<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Admin\Table;

use CWM\Component\Cwmconnect\Administrator\Table\MemberTable;
use Joomla\Database\DatabaseInterface;
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
}
