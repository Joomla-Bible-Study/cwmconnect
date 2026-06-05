<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Service\FeedToken;

use CWM\Component\Cwmconnect\Administrator\Service\FeedToken\FeedTokenService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the pure lifecycle decision {@see FeedTokenService::statusOf()}
 * — the inactivity + absolute-expiry logic added for member-managed live KML
 * feeds. DB-backed methods (issue/regenerate/listForUser/validate) are thin
 * wrappers over this and the database driver.
 */
#[CoversClass(FeedTokenService::class)]
final class FeedTokenServiceTest extends TestCase
{
    private const NOW = '2026-06-05 12:00:00';

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function row(array $overrides = []): object
    {
        return (object) array_merge([
            'id'           => 1,
            'user_id'      => 5,
            'label'        => 'Laptop',
            'created_at'   => '2026-06-01 00:00:00',
            'last_used_at' => '2026-06-01 00:00:00',
            'revoked_at'   => null,
            'expires_at'   => null,
        ], $overrides);
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC'));
    }

    #[Test]
    public function recentTokenIsActive(): void
    {
        self::assertSame(
            FeedTokenService::STATUS_ACTIVE,
            FeedTokenService::statusOf($this->row(), 90, $this->now()),
        );
    }

    #[Test]
    public function revokedBeatsEverything(): void
    {
        // Revoked AND otherwise expired — revoked wins.
        $row = $this->row([
            'revoked_at'   => '2026-06-02 00:00:00',
            'last_used_at' => '2026-01-01 00:00:00',
            'expires_at'   => '2026-01-01 00:00:00',
        ]);

        self::assertSame(
            FeedTokenService::STATUS_REVOKED,
            FeedTokenService::statusOf($row, 90, $this->now()),
        );
    }

    #[Test]
    public function absoluteExpiryInThePastIsExpired(): void
    {
        $row = $this->row(['expires_at' => '2026-06-04 11:59:59']);

        self::assertSame(
            FeedTokenService::STATUS_EXPIRED,
            FeedTokenService::statusOf($row, 0, $this->now()),
        );
    }

    #[Test]
    public function absoluteExpiryInTheFutureStaysActive(): void
    {
        $row = $this->row(['expires_at' => '2026-12-31 00:00:00']);

        self::assertSame(
            FeedTokenService::STATUS_ACTIVE,
            FeedTokenService::statusOf($row, 0, $this->now()),
        );
    }

    #[Test]
    public function inactivityBeyondWindowIsExpired(): void
    {
        // last used 2026-01-01, window 90 days -> deadline 2026-04-01 < now.
        $row = $this->row(['last_used_at' => '2026-01-01 00:00:00']);

        self::assertSame(
            FeedTokenService::STATUS_EXPIRED,
            FeedTokenService::statusOf($row, 90, $this->now()),
        );
    }

    #[Test]
    public function inactivityWithinWindowStaysActive(): void
    {
        // last used 4 days ago, window 90 -> well inside.
        self::assertSame(
            FeedTokenService::STATUS_ACTIVE,
            FeedTokenService::statusOf($this->row(), 90, $this->now()),
        );
    }

    #[Test]
    public function windowZeroDisablesInactivityExpiry(): void
    {
        // Ancient last_used but window disabled -> still active.
        $row = $this->row(['last_used_at' => '2020-01-01 00:00:00']);

        self::assertSame(
            FeedTokenService::STATUS_ACTIVE,
            FeedTokenService::statusOf($row, 0, $this->now()),
        );
    }

    #[Test]
    public function nullLastUsedFallsBackToCreatedAt(): void
    {
        // Never refreshed; created long ago -> expired against the window.
        $row = $this->row([
            'last_used_at' => null,
            'created_at'   => '2026-01-01 00:00:00',
        ]);

        self::assertSame(
            FeedTokenService::STATUS_EXPIRED,
            FeedTokenService::statusOf($row, 90, $this->now()),
        );
    }

    #[Test]
    public function nullLastUsedWithRecentCreatedStaysActive(): void
    {
        $row = $this->row([
            'last_used_at' => null,
            'created_at'   => '2026-06-01 00:00:00',
        ]);

        self::assertSame(
            FeedTokenService::STATUS_ACTIVE,
            FeedTokenService::statusOf($row, 90, $this->now()),
        );
    }

    #[Test]
    public function exactDeadlineCountsAsExpired(): void
    {
        // last_used + 90 days == now exactly -> deadline <= now -> expired.
        $row = $this->row(['last_used_at' => '2026-03-07 12:00:00']);

        self::assertSame(
            FeedTokenService::STATUS_EXPIRED,
            FeedTokenService::statusOf($row, 90, $this->now()),
        );
    }
}
