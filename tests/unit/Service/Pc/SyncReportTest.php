<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\Pc;

use CWM\Component\Cwmconnect\Administrator\Service\Pc\SyncReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SyncReport::class)]
final class SyncReportTest extends TestCase
{
    #[Test]
    public function freshReportIsZeroedAndSuccessful(): void
    {
        $report = new SyncReport();

        self::assertSame(0, $report->seen);
        self::assertSame(0, $report->added);
        self::assertSame(0, $report->updated);
        self::assertSame(0, $report->archived);
        self::assertSame(0, $report->unarchived);
        self::assertSame(0, $report->errorCount());
        self::assertTrue($report->success());
    }

    #[Test]
    public function recordErrorAppendsToErrorListAndFlipsSuccess(): void
    {
        $report = new SyncReport();
        $report->recordError(42, 'mapper exploded');

        self::assertSame(1, $report->errorCount());
        self::assertFalse($report->success());
        self::assertSame(42, $report->errors[0]['pcPersonId']);
        self::assertSame('mapper exploded', $report->errors[0]['message']);
    }

    #[Test]
    public function recordErrorAcceptsNullPcPersonIdForPreRowFailures(): void
    {
        $report = new SyncReport();
        $report->recordError(null, 'PC envelope malformed');

        self::assertNull($report->errors[0]['pcPersonId']);
    }

    #[Test]
    public function finishStampsFinishedAtAndAllowsDurationCalculation(): void
    {
        $report = new SyncReport(new \DateTimeImmutable('2026-05-15T18:00:00Z'));
        $report->finish();

        self::assertNotNull($report->finishedAt);
        self::assertNotNull($report->durationSeconds());
        self::assertGreaterThanOrEqual(0.0, $report->durationSeconds());
    }

    #[Test]
    public function durationIsNullBeforeFinish(): void
    {
        $report = new SyncReport();

        self::assertNull($report->durationSeconds());
    }

    #[Test]
    public function toArrayReturnsJsonFriendlyEnvelope(): void
    {
        $report = new SyncReport(new \DateTimeImmutable('2026-05-15T18:00:00Z'));
        $report->seen   = 5;
        $report->added  = 2;
        $report->finish();

        $arr = $report->toArray();

        self::assertSame(5, $arr['seen']);
        self::assertSame(2, $arr['added']);
        self::assertTrue($arr['success']);
        self::assertSame('2026-05-15T18:00:00+00:00', $arr['startedAt']);
        self::assertNotNull($arr['finishedAt']);
        self::assertIsFloat($arr['durationSeconds']);
    }
}
