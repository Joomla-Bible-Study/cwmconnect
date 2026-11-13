<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Privacy;

use CWM\Component\Cwmconnect\Administrator\Service\Privacy\MemberErasure;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every member column must be either blanked by a removal or deliberately
 * kept, so that a column added later can't carry personal data past one.
 */
#[CoversClass(MemberErasure::class)]
final class MemberErasureTest extends TestCase
{
    #[Test]
    public function everyMemberColumnIsBlankedOrKept(): void
    {
        $blanked = array_keys(MemberErasure::removedRow($this->db(), 990001, 7));

        self::assertSame([], array_values(array_intersect($blanked, MemberErasure::KEEP)), 'A column is both blanked and kept.');

        $covered = array_merge($blanked, MemberErasure::KEEP);
        $columns = $this->installColumns();

        sort($covered);
        sort($columns);

        self::assertSame($columns, $covered, 'Decide for each new member column whether a privacy removal blanks or keeps it.');
    }

    #[Test]
    public function aRemovedRowIsBlankArchivedAndLabelled(): void
    {
        $row = MemberErasure::removedRow($this->db(), 990001, 7);

        self::assertSame('Removed', $row['name']);
        self::assertSame('Removed', $row['lname']);
        self::assertSame('', $row['fname']);
        self::assertNull($row['email_to']);
        self::assertNull($row['user_id']);
        self::assertSame(0, $row['lat']);
        self::assertSame('0000-00-00 00:00:00', $row['birthdate']);
        self::assertSame(2, $row['published']);
        self::assertSame(0, $row['display_in_directory']);
        self::assertSame('privacy', $row['hidden_reason']);
        self::assertSame('removed-pc990001', $row['alias']);
        self::assertSame('removed-7', MemberErasure::removedRow($this->db(), 0, 7)['alias']);
    }

    private function db(): DatabaseInterface
    {
        $db = $this->createStub(DatabaseInterface::class);
        $db->method('getNullDate')->willReturn('0000-00-00 00:00:00');

        return $db;
    }

    /**
     * @return list<string>  The member table's columns, as the install SQL creates it.
     */
    private function installColumns(): array
    {
        $sql = (string) file_get_contents(\dirname(__DIR__, 4) . '/admin/sql/install.mysql.utf8.sql');

        self::assertSame(1, preg_match('/CREATE TABLE IF NOT EXISTS `#__cwmconnect_details` \((.*?)\n\)/s', $sql, $table));
        preg_match_all('/^\s+`(\w+)`/m', $table[1], $columns);

        return $columns[1];
    }
}
