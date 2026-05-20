<?php

declare(strict_types=1);

namespace CWM\Component\Cwmconnect\Tests\Site\Helper;

use CWM\Component\Cwmconnect\Site\Helper\HouseholdVisibility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Phase G coverage for spec §7.2 household visibility scoping.
 */
#[CoversClass(HouseholdVisibility::class)]
final class HouseholdVisibilityTest extends TestCase
{
    #[Test]
    public function guestViewerCannotSeeChildNames(): void
    {
        $scope = HouseholdVisibility::scope(null, 5);

        self::assertSame(HouseholdVisibility::GUEST, $scope);
        self::assertFalse(HouseholdVisibility::showsChildNames($scope));
    }

    #[Test]
    public function viewerInSameHouseholdSeesChildNames(): void
    {
        $scope = HouseholdVisibility::scope(5, 5);

        self::assertSame(HouseholdVisibility::SAME_HOUSEHOLD, $scope);
        self::assertTrue(HouseholdVisibility::showsChildNames($scope));
    }

    #[Test]
    public function viewerInDifferentHouseholdGetsOtherHousehold(): void
    {
        $scope = HouseholdVisibility::scope(5, 9);

        self::assertSame(HouseholdVisibility::OTHER_HOUSEHOLD, $scope);
        self::assertFalse(HouseholdVisibility::showsChildNames($scope));
    }

    #[Test]
    public function targetWithNoHouseholdIsTreatedAsOther(): void
    {
        // Standalone member without a familyunit row: viewer can't be in
        // the same household by definition.
        self::assertSame(
            HouseholdVisibility::OTHER_HOUSEHOLD,
            HouseholdVisibility::scope(5, null),
        );
        self::assertSame(
            HouseholdVisibility::OTHER_HOUSEHOLD,
            HouseholdVisibility::scope(5, 0),
        );
    }

    #[Test]
    public function viewerWithoutHouseholdIsAlwaysGuestScope(): void
    {
        // A logged-in user not linked to a directory row gets the
        // strictest scope — same as a true guest. They never see kids.
        self::assertSame(HouseholdVisibility::GUEST, HouseholdVisibility::scope(0, 5));
        self::assertSame(HouseholdVisibility::GUEST, HouseholdVisibility::scope(null, 5));
    }
}
