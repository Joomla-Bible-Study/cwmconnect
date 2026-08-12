<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pairing;

use CWM\Component\Cwmconnect\Administrator\Service\Pairing\MemberGroupSync;
use Joomla\Database\DatabaseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the switched-off and no-op paths of {@see MemberGroupSync}.
 *
 * These are the load-bearing guards: the feature ships disabled so that
 * installing or updating never silently rearranges anyone's group membership,
 * and that promise is only kept if `reconcile()` touches nothing at all when
 * no group is configured. Asserting "the database was never queried" is
 * stronger than asserting a return value, because the failure mode being
 * guarded against is a *side effect*.
 *
 * The branches that do act call `UserHelper`, a CMS static that the unit
 * bootstrap cannot autoload, so they are covered by live verification rather
 * than here.
 */
#[CoversClass(MemberGroupSync::class)]
final class MemberGroupSyncTest extends TestCase
{
    /**
     * A database that fails the test if anything asks it to run a query.
     */
    private function untouchableDatabase(): DatabaseInterface
    {
        $db = $this->createMock(DatabaseInterface::class);
        $db->expects(self::never())->method('setQuery');

        return $db;
    }

    #[Test]
    public function doesNothingWhenNoGroupConfigured(): void
    {
        new MemberGroupSync($this->untouchableDatabase(), 0)->reconcile(42);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function doesNothingWhenGroupIdIsNegative(): void
    {
        new MemberGroupSync($this->untouchableDatabase(), -1)->reconcile(42);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function doesNothingForAGuest(): void
    {
        new MemberGroupSync($this->untouchableDatabase(), 10)->reconcile(0);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function doesNothingForANegativeUserId(): void
    {
        new MemberGroupSync($this->untouchableDatabase(), 10)->reconcile(-5);

        $this->addToAssertionCount(1);
    }
}
